<?php
require __DIR__ . '/../helpers.php';
$user = current_user();
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}
$config = require __DIR__ . '/../config.php';
$limit = $config['security']['rate_limit']['spin'];
if (rate_limited('spin', $limit['window'], $limit['max'])) {
    json_response(['error' => 'Слишком много спинов.'], 429);
}
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$slotKey = $input['game'] ?? '';
$slotConfig = slot_config($slotKey);
$minBet = (float) $config['game']['min_bet'];
$maxBet = (float) $config['game']['max_bet'];
$bet = (float) ($input['bet'] ?? $minBet);
if ($bet < $minBet || $bet > $maxBet) {
    json_response(['error' => 'Ставка вне лимитов.'], 400);
}
$balance = user_balance((int) $user['id']);
if ($balance < $bet) {
    json_response(['error' => t('insufficient_funds')], 400);
}
$columns = (int) $slotConfig['cols'];
$rows = (int) $slotConfig['rows'];
$grid = [];
$counts = [];
$scatterCount = 0;
$winCells = [];
$bestSymbol = null;
$bestCount = 0;
$feature = null;
$featureBonus = 0.0;
$multiplier = 0.0;
$winType = $slotConfig['win_type'] ?? 'count';
$payouts = $slotConfig['payouts'] ?? [];
$rareSymbols = $slotConfig['rare_symbols'] ?? [];
$rareBonus = (float) ($slotConfig['rare_bonus'] ?? 0);
$symbols = $slotConfig['symbols'];
$weights = $slotConfig['weights'] ?? array_fill(0, count($symbols), 1);
$totalWeight = array_sum($weights);

$pickSymbol = static function () use ($symbols, $weights, $totalWeight): string {
    $roll = random_int(1, $totalWeight);
    $current = 0;
    foreach ($weights as $index => $weight) {
        $current += $weight;
        if ($roll <= $current) {
            return $symbols[$index];
        }
    }
    return $symbols[array_key_first($symbols)];
};

for ($x = 0; $x < $columns; $x++) {
    for ($y = 0; $y < $rows; $y++) {
        $symbol = $pickSymbol();
        $grid[$x][$y] = $symbol;
        $counts[$symbol] = ($counts[$symbol] ?? 0) + 1;
        if ($symbol === $slotConfig['scatter']) {
            $scatterCount++;
        }
    }
}
$updateBest = static function (string $symbol, int $count, array $cells) use (&$bestCount, &$bestSymbol, &$winCells): void {
    if ($count > $bestCount) {
        $bestCount = $count;
        $bestSymbol = $symbol;
        $winCells = $cells;
    }
};

$processLine = static function (array $line, array $grid, callable $updateBest): void {
    $currentSymbol = null;
    $currentCells = [];
    foreach ($line as [$x, $y]) {
        $symbol = $grid[$x][$y];
        if ($symbol !== $currentSymbol) {
            if ($currentSymbol !== null) {
                $updateBest($currentSymbol, count($currentCells), $currentCells);
            }
            $currentSymbol = $symbol;
            $currentCells = [[$x, $y]];
        } else {
            $currentCells[] = [$x, $y];
        }
    }
    if ($currentSymbol !== null) {
        $updateBest($currentSymbol, count($currentCells), $currentCells);
    }
};

switch ($winType) {
    case 'cluster':
        $visited = array_fill(0, $columns, array_fill(0, $rows, false));
        foreach ($grid as $x => $col) {
            foreach ($col as $y => $symbol) {
                if ($visited[$x][$y]) {
                    continue;
                }
                $queue = [[$x, $y]];
                $visited[$x][$y] = true;
                $cluster = [];
                while ($queue) {
                    [$cx, $cy] = array_shift($queue);
                    $cluster[] = [$cx, $cy];
                    foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as $offset) {
                        $nx = $cx + $offset[0];
                        $ny = $cy + $offset[1];
                        if ($nx < 0 || $nx >= $columns || $ny < 0 || $ny >= $rows) {
                            continue;
                        }
                        if ($visited[$nx][$ny]) {
                            continue;
                        }
                        if ($grid[$nx][$ny] !== $symbol) {
                            continue;
                        }
                        $visited[$nx][$ny] = true;
                        $queue[] = [$nx, $ny];
                    }
                }
                $updateBest($symbol, count($cluster), $cluster);
            }
        }
        break;
    case 'row':
        for ($y = 0; $y < $rows; $y++) {
            $line = [];
            for ($x = 0; $x < $columns; $x++) {
                $line[] = [$x, $y];
            }
            $processLine($line, $grid, $updateBest);
        }
        break;
    case 'column':
        for ($x = 0; $x < $columns; $x++) {
            $line = [];
            for ($y = 0; $y < $rows; $y++) {
                $line[] = [$x, $y];
            }
            $processLine($line, $grid, $updateBest);
        }
        break;
    case 'diagonal':
        for ($startX = 0; $startX < $columns; $startX++) {
            $line = [];
            for ($x = $startX, $y = 0; $x < $columns && $y < $rows; $x++, $y++) {
                $line[] = [$x, $y];
            }
            $processLine($line, $grid, $updateBest);
        }
        for ($startY = 1; $startY < $rows; $startY++) {
            $line = [];
            for ($x = 0, $y = $startY; $x < $columns && $y < $rows; $x++, $y++) {
                $line[] = [$x, $y];
            }
            $processLine($line, $grid, $updateBest);
        }
        for ($startX = 0; $startX < $columns; $startX++) {
            $line = [];
            for ($x = $startX, $y = $rows - 1; $x < $columns && $y >= 0; $x++, $y--) {
                $line[] = [$x, $y];
            }
            $processLine($line, $grid, $updateBest);
        }
        for ($startY = $rows - 2; $startY >= 0; $startY--) {
            $line = [];
            for ($x = 0, $y = $startY; $x < $columns && $y >= 0; $x++, $y--) {
                $line[] = [$x, $y];
            }
            $processLine($line, $grid, $updateBest);
        }
        break;
    case 'edge':
        $edgeCounts = [];
        for ($x = 0; $x < $columns; $x++) {
            foreach ([0, $rows - 1] as $y) {
                $symbol = $grid[$x][$y];
                $edgeCounts[$symbol] = ($edgeCounts[$symbol] ?? 0) + 1;
            }
        }
        for ($y = 1; $y < $rows - 1; $y++) {
            foreach ([0, $columns - 1] as $x) {
                $symbol = $grid[$x][$y];
                $edgeCounts[$symbol] = ($edgeCounts[$symbol] ?? 0) + 1;
            }
        }
        foreach ($edgeCounts as $symbol => $count) {
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestSymbol = $symbol;
            }
        }
        if ($bestSymbol) {
            for ($x = 0; $x < $columns; $x++) {
                foreach ([0, $rows - 1] as $y) {
                    if ($grid[$x][$y] === $bestSymbol) {
                        $winCells[] = [$x, $y];
                    }
                }
            }
            for ($y = 1; $y < $rows - 1; $y++) {
                foreach ([0, $columns - 1] as $x) {
                    if ($grid[$x][$y] === $bestSymbol) {
                        $winCells[] = [$x, $y];
                    }
                }
            }
        }
        break;
    case 'corner':
        $cornerCells = [
            [0, 0],
            [0, $rows - 1],
            [$columns - 1, 0],
            [$columns - 1, $rows - 1],
        ];
        $cornerCounts = [];
        foreach ($cornerCells as [$x, $y]) {
            $symbol = $grid[$x][$y];
            $cornerCounts[$symbol] = ($cornerCounts[$symbol] ?? 0) + 1;
        }
        foreach ($cornerCounts as $symbol => $count) {
            $updateBest($symbol, $count, array_values(array_filter($cornerCells, fn($cell) => $grid[$cell[0]][$cell[1]] === $symbol)));
        }
        break;
    case 'center':
        $centerCells = [];
        $startX = max(1, (int) floor($columns / 2) - 1);
        $startY = max(1, (int) floor($rows / 2) - 1);
        $endX = min($columns - 2, $startX + 2);
        $endY = min($rows - 2, $startY + 2);
        for ($x = $startX; $x <= $endX; $x++) {
            for ($y = $startY; $y <= $endY; $y++) {
                $centerCells[] = [$x, $y];
            }
        }
        $centerCounts = [];
        foreach ($centerCells as [$x, $y]) {
            $symbol = $grid[$x][$y];
            $centerCounts[$symbol] = ($centerCounts[$symbol] ?? 0) + 1;
        }
        foreach ($centerCounts as $symbol => $count) {
            $cells = array_values(array_filter($centerCells, fn($cell) => $grid[$cell[0]][$cell[1]] === $symbol));
            $updateBest($symbol, $count, $cells);
        }
        break;
    case 'cross':
        $crossCells = [];
        $centerX = (int) floor($columns / 2);
        $centerY = (int) floor($rows / 2);
        for ($x = 0; $x < $columns; $x++) {
            $crossCells[] = [$x, $centerY];
        }
        for ($y = 0; $y < $rows; $y++) {
            if ($y === $centerY) {
                continue;
            }
            $crossCells[] = [$centerX, $y];
        }
        $crossCounts = [];
        foreach ($crossCells as [$x, $y]) {
            $symbol = $grid[$x][$y];
            $crossCounts[$symbol] = ($crossCounts[$symbol] ?? 0) + 1;
        }
        foreach ($crossCounts as $symbol => $count) {
            $cells = array_values(array_filter($crossCells, fn($cell) => $grid[$cell[0]][$cell[1]] === $symbol));
            $updateBest($symbol, $count, $cells);
        }
        break;
    case 'count':
    default:
        foreach ($counts as $symbol => $count) {
            $updateBest($symbol, $count, []);
        }
        if ($bestSymbol) {
            foreach ($grid as $x => $col) {
                foreach ($col as $y => $symbol) {
                    if ($symbol === $bestSymbol) {
                        $winCells[] = [$x, $y];
                    }
                }
            }
        }
        break;
}

if (!$bestSymbol) {
    $bestSymbol = array_key_first($counts);
    $bestCount = $counts[$bestSymbol] ?? 0;
}

foreach ($payouts as $tier) {
    $threshold = (int) ($tier['count'] ?? 0);
    if ($bestCount >= $threshold) {
        $multiplier = max($multiplier, (float) ($tier['multiplier'] ?? 0));
    }
}

if ($bestSymbol && in_array($bestSymbol, $rareSymbols, true) && $multiplier > 0) {
    $multiplier += $rareBonus;
}

if ($multiplier <= 0) {
    $winCells = [];
}

$featureTrigger = $slotConfig['feature_trigger'] ?? null;
$featureThreshold = (int) ($slotConfig['feature_threshold'] ?? 0);
$featureMin = (float) ($slotConfig['feature_bonus_min'] ?? 0);
$featureMax = (float) ($slotConfig['feature_bonus_max'] ?? $featureMin);
if ($featureTrigger) {
    $triggered = false;
    if ($featureTrigger === 'scatter') {
        $triggered = $scatterCount >= $featureThreshold;
    } elseif ($featureTrigger === 'best_count') {
        $triggered = $bestCount >= $featureThreshold;
    }
    if ($triggered && $featureMax > 0) {
        $feature = $slotConfig['feature_tag'] ?? $feature;
        $minStep = (int) round($featureMin * 10);
        $maxStep = (int) round($featureMax * 10);
        $featureBonus = random_int(min($minStep, $maxStep), max($minStep, $maxStep)) / 10;
        $multiplier += $featureBonus;
    }
} else {
    switch ($slotConfig['type']) {
        case 'cascade':
            if ($scatterCount >= 3) {
                $feature = 'free_spins';
                $featureBonus = 1.1;
                $multiplier += $featureBonus;
            }
            break;
        case 'sticky':
            if ($scatterCount >= 2) {
                $feature = 'sticky_wilds';
                $featureBonus = 0.7;
                $multiplier += $featureBonus;
            }
            break;
        case 'scatter':
            if ($scatterCount >= 3) {
                $feature = 'sky_multiplier';
                $featureBonus = random_int(8, 18) / 10;
                $multiplier += $featureBonus;
            }
            break;
        case 'cluster':
            if ($bestCount >= 12) {
                $feature = 'cluster_burst';
                $featureBonus = 0.8;
                $multiplier += $featureBonus;
            }
            break;
        case 'burst':
            if ($scatterCount >= 3) {
                $feature = 'gem_storm';
                $featureBonus = 1.0;
                $multiplier += $featureBonus;
            }
            break;
        case 'orbit':
            if ($scatterCount >= 3) {
                $feature = 'orbit_bonus';
                $featureBonus = 0.9;
                $multiplier += $featureBonus;
            }
            break;
    }
}
$win = round($bet * $multiplier, 2);
$newBalance = $balance - $bet + $win;

db()->prepare('UPDATE balances SET balance = ? WHERE user_id = ?')
    ->execute([$newBalance, $user['id']]);

db()->prepare('INSERT INTO game_logs (user_id, slot, bet, win, meta) VALUES (?, ?, ?, ?, ?)')
    ->execute([$user['id'], $slotKey, $bet, $win, json_encode([
        'grid' => $grid,
        'scatter' => $scatterCount,
        'feature' => $feature,
        'feature_bonus' => $featureBonus,
    ])]);

db()->prepare('UPDATE users SET total_wins = total_wins + ? WHERE id = ?')
    ->execute([$win, $user['id']]);

update_mission_progress((int) $user['id'], 'slots_spins', 1);
update_mission_progress((int) $user['id'], 'slots_bet', $bet);
if ($win > 0) {
    update_mission_progress((int) $user['id'], 'slots_win', $win);
}
update_tournament_progress((int) $user['id'], 'slots', $bet, $win);

json_response([
    'win' => $win,
    'grid' => $grid,
    'scatter' => $scatterCount,
    'balance' => $newBalance,
    'symbol' => $bestSymbol,
    'feature' => $feature,
    'feature_bonus' => $featureBonus,
    'multiplier' => $multiplier,
    'win_cells' => $winCells,
    'win_type' => $winType,
    'count' => $bestCount,
]);
