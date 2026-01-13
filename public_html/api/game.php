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
for ($x = 0; $x < $columns; $x++) {
    for ($y = 0; $y < $rows; $y++) {
        $symbol = $slotConfig['symbols'][random_int(0, count($slotConfig['symbols']) - 1)];
        $grid[$x][$y] = $symbol;
        $counts[$symbol] = ($counts[$symbol] ?? 0) + 1;
        if ($symbol === $slotConfig['scatter']) {
            $scatterCount++;
        }
    }
}
$bestSymbol = array_key_first($counts);
foreach ($counts as $symbol => $count) {
    if ($count > $counts[$bestSymbol]) {
        $bestSymbol = $symbol;
    }
}
$bestCount = $counts[$bestSymbol] ?? 0;
$multiplier = 0;
$feature = null;
switch ($slotConfig['type']) {
    case 'cascade':
        if ($bestCount >= 7) {
            $multiplier = ($bestCount - 6) * 0.5;
        }
        if ($scatterCount >= 3) {
            $feature = 'free_spins';
            $multiplier += 1.5;
        }
        break;
    case 'ways':
        if ($bestCount >= 6) {
            $multiplier = ($bestCount - 5) * 0.4;
        }
        break;
    case 'sticky':
        if ($bestCount >= 7) {
            $multiplier = ($bestCount - 6) * 0.6;
        }
        if ($scatterCount >= 2) {
            $feature = 'sticky_wilds';
            $multiplier += 0.8;
        }
        break;
    case 'scatter':
        if ($scatterCount >= 3) {
            $feature = 'sky_multiplier';
            $multiplier = random_int(10, 25) / 10;
        } elseif ($bestCount >= 7) {
            $multiplier = ($bestCount - 6) * 0.5;
        }
        break;
    case 'cluster':
        if ($bestCount >= 9) {
            $multiplier = ($bestCount - 8) * 0.7;
        }
        if ($bestCount >= 12) {
            $feature = 'cluster_burst';
            $multiplier += 1.2;
        }
        break;
    case 'burst':
        if ($bestCount >= 8) {
            $multiplier = ($bestCount - 7) * 0.55;
        }
        if ($scatterCount >= 3) {
            $feature = 'gem_storm';
            $multiplier += 1.4;
        }
        break;
    case 'orbit':
        if ($bestCount >= 6) {
            $multiplier = ($bestCount - 5) * 0.45;
        }
        if ($scatterCount >= 3) {
            $feature = 'orbit_bonus';
            $multiplier += 1.1;
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
]);
