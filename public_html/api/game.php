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
if ($winType === 'cluster') {
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
            if (count($cluster) > $bestCount) {
                $bestCount = count($cluster);
                $bestSymbol = $symbol;
                $winCells = $cluster;
            }
        }
    }
} else {
    $bestSymbol = array_key_first($counts);
    foreach ($counts as $symbol => $count) {
        if ($count > $bestCount) {
            $bestCount = $count;
            $bestSymbol = $symbol;
        }
    }
    foreach ($grid as $x => $col) {
        foreach ($col as $y => $symbol) {
            if ($symbol === $bestSymbol) {
                $winCells[] = [$x, $y];
            }
        }
    }
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

switch ($slotConfig['type']) {
    case 'cascade':
        if ($scatterCount >= 3) {
            $feature = 'free_spins';
            $multiplier += 1.1;
        }
        break;
    case 'sticky':
        if ($scatterCount >= 2) {
            $feature = 'sticky_wilds';
            $multiplier += 0.7;
        }
        break;
    case 'scatter':
        if ($scatterCount >= 3) {
            $feature = 'sky_multiplier';
            $multiplier += random_int(8, 18) / 10;
        }
        break;
    case 'cluster':
        if ($bestCount >= 12) {
            $feature = 'cluster_burst';
            $multiplier += 0.8;
        }
        break;
    case 'burst':
        if ($scatterCount >= 3) {
            $feature = 'gem_storm';
            $multiplier += 1.0;
        }
        break;
    case 'orbit':
        if ($scatterCount >= 3) {
            $feature = 'orbit_bonus';
            $multiplier += 0.9;
        }
        break;
}
$win = round($bet * $multiplier, 2);
$newBalance = $balance - $bet + $win;

db()->prepare('UPDATE balances SET balance = ? WHERE user_id = ?')
    ->execute([$newBalance, $user['id']]);

db()->prepare('INSERT INTO game_logs (user_id, slot, bet, win, meta) VALUES (?, ?, ?, ?, ?)')
    ->execute([$user['id'], $slotKey, $bet, $win, json_encode(['grid' => $grid, 'scatter' => $scatterCount])]);

db()->prepare('UPDATE users SET total_wins = total_wins + ? WHERE id = ?')
    ->execute([$win, $user['id']]);

json_response([
    'win' => $win,
    'grid' => $grid,
    'scatter' => $scatterCount,
    'balance' => $newBalance,
    'symbol' => $bestSymbol,
    'feature' => $feature,
    'multiplier' => $multiplier,
    'win_cells' => $winCells,
    'win_type' => $winType,
    'count' => $bestCount,
]);
