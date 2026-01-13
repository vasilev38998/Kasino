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
$slotKey = $input['game'] ?? 'unknown';
$slotConfigs = [
    'aurora-cascade' => ['symbols' => ['A', 'K', 'Q', 'J', '10', '9', '★', '✦'], 'scatter' => '★'],
    'cosmic-cluster' => ['symbols' => ['A', 'K', 'Q', 'J', '10', '9', '✶', '✹'], 'scatter' => '✶'],
    'dragon-sticky' => ['symbols' => ['A', 'K', 'Q', 'J', '10', '9', '🐉', '🔥'], 'scatter' => '🔥'],
    'sky-titans' => ['symbols' => ['A', 'K', 'Q', 'J', '10', '9', '⚡', '☁'], 'scatter' => '⚡'],
    'sugar-bloom' => ['symbols' => ['🍬', '🍭', '🍫', '🍒', '🍋', '🍇', '⭐', '💎'], 'scatter' => '⭐'],
    'zenith-gems' => ['symbols' => ['🔷', '🔶', '🔺', '🔸', '💎', '✨', 'A', 'K'], 'scatter' => '✨'],
    'orbit-jewels' => ['symbols' => ['🪐', '🌙', '⭐', '💠', 'A', 'K', 'Q', 'J'], 'scatter' => '⭐'],
];
$slotConfig = $slotConfigs[$slotKey] ?? $slotConfigs['aurora-cascade'];
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
$columns = 6;
$rows = 5;
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
if ($bestCount >= 8) {
    $multiplier = ($bestCount - 7) * 0.6;
}
if ($slotKey === 'sky-titans' && $scatterCount >= 3) {
    $multiplier += random_int(2, 8) / 10;
}
if ($slotKey === 'sugar-bloom' && $bestCount >= 10) {
    $multiplier += random_int(5, 20) / 10;
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
]);
