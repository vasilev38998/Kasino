<?php
require __DIR__ . '/../helpers.php';
$user = current_user();
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';
if ($action === 'daily') {
    $stmt = db()->prepare('SELECT claimed_at FROM bonuses WHERE user_id = ? AND type = "daily" ORDER BY claimed_at DESC LIMIT 1');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if ($row && strtotime($row['claimed_at']) > time() - 86400) {
        json_response(['error' => 'Бонус уже получен сегодня.'], 400);
    }
    $config = require __DIR__ . '/../config.php';
    $amount = $config['bonuses']['daily_amount'];
    db()->prepare('INSERT INTO bonuses (user_id, type, amount, claimed_at) VALUES (?, "daily", ?, NOW())')
        ->execute([$user['id'], $amount]);
    db()->prepare('INSERT INTO balances (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
        ->execute([$user['id'], $amount]);
    json_response(['amount' => $amount]);
}
json_response(['error' => 'Неверный запрос.'], 400);
