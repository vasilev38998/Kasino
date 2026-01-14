<?php
require __DIR__ . '/../helpers.php';
$user = current_user();
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}
$config = require __DIR__ . '/../config.php';
$limit = $config['security']['rate_limit']['spin'];
if (rate_limited('minigame', $limit['window'], $limit['max'])) {
    json_response(['error' => 'Слишком много попыток.'], 429);
}
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$game = $input['game'] ?? '';
$bet = (float) ($input['bet'] ?? 0);
$minBet = (float) $config['game']['min_bet'];
$maxBet = (float) $config['game']['max_bet'];
if ($bet < $minBet || $bet > $maxBet) {
    json_response(['error' => 'Ставка вне лимитов.'], 400);
}
$balance = user_balance((int) $user['id']);
if ($balance < $bet) {
    json_response(['error' => t('insufficient_funds')], 400);
}
$win = 0;
$meta = [];
if ($game === 'coin') {
    $choice = $input['choice'] ?? 'heads';
    $mode = $input['mode'] ?? 'classic';
    $roll = random_int(1, 100);
    if ($mode === 'boost') {
        $result = $roll <= 35 ? $choice : ($choice === 'heads' ? 'tails' : 'heads');
        $multiplier = 3.0;
    } else {
        $result = $roll <= 50 ? $choice : ($choice === 'heads' ? 'tails' : 'heads');
        $multiplier = 2.0;
    }
    $win = $choice === $result ? $bet * $multiplier : 0;
    $meta = ['choice' => $choice, 'result' => $result, 'mode' => $mode, 'multiplier' => $multiplier];
} elseif ($game === 'plinko') {
    $difficulty = $input['difficulty'] ?? 'easy';
    $pins = (int) ($input['pins'] ?? 16);
    $pins = max(8, min(16, $pins));
    $baseMultipliers = [
        'easy' => [0.3, 0.5, 0.7, 0.9, 1.1, 1.4, 1.8, 2.4, 1.8, 1.4, 1.1, 0.9, 0.7, 0.5, 0.3],
        'medium' => [0, 0.2, 0.4, 0.6, 0.9, 1.3, 1.8, 2.6, 1.8, 1.3, 0.9, 0.6, 0.4, 0.2, 0],
        'hard' => [0, 0.1, 0.2, 0.4, 0.7, 1.1, 1.7, 2.8, 4.0, 2.8, 1.7, 1.1, 0.7, 0.4, 0.2, 0.1, 0],
    ];
    $baseWeights = [
        'easy' => [6, 8, 10, 12, 14, 16, 18, 22, 18, 16, 14, 12, 10, 8, 6],
        'medium' => [4, 6, 8, 10, 12, 16, 20, 26, 20, 16, 12, 10, 8, 6, 4],
        'hard' => [3, 4, 6, 8, 10, 12, 16, 20, 24, 20, 16, 12, 10, 8, 6, 4, 3],
    ];
    $slotsCount = max(7, min(15, $pins - 1));
    $multipliersBase = $baseMultipliers[$difficulty] ?? $baseMultipliers['easy'];
    $weightsBase = $baseWeights[$difficulty] ?? $baseWeights['easy'];
    $offset = (int) floor((count($multipliersBase) - $slotsCount) / 2);
    $multipliers = array_slice($multipliersBase, $offset, $slotsCount);
    $weights = array_slice($weightsBase, $offset, $slotsCount);
    $plinkoConfig = [
        'rows' => $slotsCount - 1,
        'multipliers' => $multipliers,
        'weights' => $weights,
    ];
    $multipliers = $plinkoConfig['multipliers'];
    $weights = $plinkoConfig['weights'];
    $total = array_sum($weights);
    $pick = random_int(1, $total);
    $current = 0;
    $index = 0;
    foreach ($weights as $i => $weight) {
        $current += $weight;
        if ($pick <= $current) {
            $index = $i;
            break;
        }
    }
    $multiplier = $multipliers[$index];
    $win = $bet * $multiplier;
    $meta = [
        'difficulty' => $difficulty,
        'rows' => $plinkoConfig['rows'],
        'multipliers' => $multipliers,
        'index' => $index,
        'multiplier' => $multiplier,
        'pins' => $pins,
    ];
} elseif ($game === 'dice') {
    $pick = (int) ($input['pick'] ?? 1);
    $mode = $input['mode'] ?? 'exact';
    $pickSecond = (int) ($input['pick_second'] ?? 1);
    if ($pick < 1 || $pick > 6 || $pickSecond < 1 || $pickSecond > 6) {
        json_response(['error' => 'Некорректный выбор.'], 400);
    }
    $roll = random_int(1, 6);
    if ($mode === 'dual') {
        if ($pick === $pickSecond) {
            json_response(['error' => 'Выберите две разные грани.'], 400);
        }
        $multiplier = ($roll === $pick || $roll === $pickSecond) ? 3 : 0;
    } else {
        $multiplier = $pick === $roll ? 6 : 0;
    }
    $win = $bet * $multiplier;
    $meta = ['pick' => $pick, 'pick_second' => $pickSecond, 'roll' => $roll, 'multiplier' => $multiplier, 'mode' => $mode];
} elseif ($game === 'highlow') {
    $pick = $input['pick'] ?? 'high';
    if (!in_array($pick, ['high', 'low'], true)) {
        json_response(['error' => 'Некорректный выбор.'], 400);
    }
    $risk = $input['risk'] ?? 'safe';
    $value = random_int(1, 13);
    $suits = ['hearts', 'spades', 'diamonds', 'clubs'];
    $suit = $suits[random_int(0, count($suits) - 1)];
    $isHigh = $value >= 8;
    $isLow = $value <= 6;
    $winMultiplier = $risk === 'risk' ? 2.4 : 1.7;
    if ($value === 7 && $risk !== 'risk') {
        $multiplier = 1;
    } else {
        $multiplier = ($pick === 'high' && $isHigh) || ($pick === 'low' && $isLow) ? $winMultiplier : 0;
    }
    $win = $bet * $multiplier;
    $meta = [
        'pick' => $pick,
        'value' => $value,
        'suit' => $suit,
        'multiplier' => $multiplier,
        'outcome' => ($value === 7 && $risk !== 'risk') ? 'push' : ($multiplier > 0 ? 'win' : 'lose'),
        'risk' => $risk,
    ];
} elseif ($game === 'treasure') {
    $pick = (int) ($input['pick'] ?? 1);
    if ($pick < 1 || $pick > 3) {
        json_response(['error' => 'Некорректный выбор.'], 400);
    }
    $mode = $input['mode'] ?? 'map';
    if ($mode === 'relic') {
        $multipliers = [0, 0.4, 1.2, 2.8, 4.5];
        $weights = [28, 26, 20, 16, 10];
    } else {
        $multipliers = [0, 0.6, 1.5, 2.5, 3.5];
        $weights = [24, 26, 20, 18, 12];
    }
    $total = array_sum($weights);
    $roll = random_int(1, $total);
    $current = 0;
    $index = 0;
    foreach ($weights as $i => $weight) {
        $current += $weight;
        if ($roll <= $current) {
            $index = $i;
            break;
        }
    }
    $multiplier = $multipliers[$index];
    $win = $bet * $multiplier;
    $meta = [
        'pick' => $pick,
        'multiplier' => $multiplier,
        'mode' => $mode,
    ];
} elseif ($game === 'wheel') {
    $mode = $input['mode'] ?? 'classic';
    if ($mode === 'vip') {
        $multipliers = [0, 0.6, 1.1, 1.8, 2.6, 4.0, 6.5];
        $weights = [24, 20, 16, 12, 10, 8, 4];
    } else {
        $multipliers = [0, 0.4, 0.8, 1.2, 1.6, 2.2, 3.5];
        $weights = [26, 20, 18, 14, 10, 8, 4];
    }
    $total = array_sum($weights);
    $roll = random_int(1, $total);
    $current = 0;
    $index = 0;
    foreach ($weights as $i => $weight) {
        $current += $weight;
        if ($roll <= $current) {
            $index = $i;
            break;
        }
    }
    $multiplier = $multipliers[$index];
    $win = $bet * $multiplier;
    $meta = [
        'index' => $index,
        'multiplier' => $multiplier,
        'slices' => $multipliers,
        'mode' => $mode,
    ];
} else {
    json_response(['error' => 'Неизвестная игра.'], 400);
}
$newBalance = $balance - $bet + $win;

db()->prepare('UPDATE balances SET balance = ? WHERE user_id = ?')
    ->execute([$newBalance, $user['id']]);

db()->prepare('INSERT INTO game_logs (user_id, slot, bet, win, meta) VALUES (?, ?, ?, ?, ?)')
    ->execute([$user['id'], 'minigame-' . $game, $bet, $win, json_encode($meta)]);

db()->prepare('UPDATE users SET total_wins = total_wins + ? WHERE id = ?')
    ->execute([$win, $user['id']]);

json_response(['win' => $win, 'balance' => $newBalance, 'meta' => $meta]);
