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
    $result = random_int(0, 1) === 1 ? 'heads' : 'tails';
    $win = $choice === $result ? $bet * 2 : 0;
    $meta = ['choice' => $choice, 'result' => $result];
} elseif ($game === 'plinko') {
    $difficulty = $input['difficulty'] ?? 'easy';
    $plinkoConfigs = [
        'easy' => [
            'rows' => 6,
            'multipliers' => [0.2, 0.5, 0.8, 1, 1.2, 1, 0.8, 0.5, 0.2],
            'weights' => [8, 12, 16, 20, 24, 20, 16, 12, 8],
        ],
        'medium' => [
            'rows' => 8,
            'multipliers' => [0, 0.2, 0.5, 0.8, 1, 1.5, 2, 1.5, 1, 0.8, 0.5, 0.2, 0],
            'weights' => [6, 8, 10, 14, 18, 22, 26, 22, 18, 14, 10, 8, 6],
        ],
        'hard' => [
            'rows' => 10,
            'multipliers' => [0, 0.1, 0.2, 0.5, 0.8, 1, 1.5, 2, 3, 2, 1.5, 1, 0.8, 0.5, 0.2, 0.1, 0],
            'weights' => [4, 6, 8, 10, 14, 18, 22, 26, 20, 26, 22, 18, 14, 10, 8, 6, 4],
        ],
    ];
    $plinkoConfig = $plinkoConfigs[$difficulty] ?? $plinkoConfigs['easy'];
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
    ];
} elseif ($game === 'dice') {
    $pick = (int) ($input['pick'] ?? 1);
    if ($pick < 1 || $pick > 6) {
        json_response(['error' => 'Некорректный выбор.'], 400);
    }
    $roll = random_int(1, 6);
    $multiplier = $pick === $roll ? 6 : 0;
    $win = $bet * $multiplier;
    $meta = ['pick' => $pick, 'roll' => $roll, 'multiplier' => $multiplier];
} elseif ($game === 'highlow') {
    $pick = $input['pick'] ?? 'high';
    if (!in_array($pick, ['high', 'low'], true)) {
        json_response(['error' => 'Некорректный выбор.'], 400);
    }
    $value = random_int(1, 13);
    $suits = ['hearts', 'spades', 'diamonds', 'clubs'];
    $suit = $suits[random_int(0, count($suits) - 1)];
    $isHigh = $value >= 8;
    $isLow = $value <= 6;
    if ($value === 7) {
        $multiplier = 1;
    } else {
        $multiplier = ($pick === 'high' && $isHigh) || ($pick === 'low' && $isLow) ? 2 : 0;
    }
    $win = $bet * $multiplier;
    $meta = [
        'pick' => $pick,
        'value' => $value,
        'suit' => $suit,
        'multiplier' => $multiplier,
        'outcome' => $value === 7 ? 'push' : ($multiplier > 0 ? 'win' : 'lose'),
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
