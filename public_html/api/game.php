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
$bet = max(10, (float) ($input['bet'] ?? 10));
$balance = user_balance((int) $user['id']);
if ($balance < $bet) {
    json_response(['error' => t('insufficient_funds')], 400);
}
$combo = random_int(1, 100);
$multiplier = match (true) {
    $combo > 95 => 20,
    $combo > 85 => 10,
    $combo > 70 => 5,
    $combo > 55 => 3,
    $combo > 40 => 2,
    default => 0,
};
$win = $bet * $multiplier;
$newBalance = $balance - $bet + $win;

db()->prepare('UPDATE balances SET balance = ? WHERE user_id = ?')
    ->execute([$newBalance, $user['id']]);

db()->prepare('INSERT INTO game_logs (user_id, slot, bet, win, meta) VALUES (?, ?, ?, ?, ?)')
    ->execute([$user['id'], $input['game'] ?? 'unknown', $bet, $win, json_encode(['combo' => $combo])]);

db()->prepare('UPDATE users SET total_wins = total_wins + ? WHERE id = ?')
    ->execute([$win, $user['id']]);

json_response(['win' => $win, 'combo' => $combo, 'balance' => $newBalance]);
