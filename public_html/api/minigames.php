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
    $multipliers = [0, 0.5, 1, 1.5, 2, 3, 5];
    $weights = [8, 12, 18, 20, 18, 12, 6];
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
    $meta = ['multiplier' => $multiplier];
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
