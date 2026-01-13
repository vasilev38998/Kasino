<?php
require __DIR__ . '/../helpers.php';
$user = current_user();
if (!$user) {
    json_response(['balance' => 0]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $amount = (float) ($input['amount'] ?? 0);
    $balance = user_balance((int) $user['id']);
    if ($action === 'deposit') {
        if ($amount <= 0) {
            json_response(['error' => 'Сумма некорректна.'], 400);
        }
        $config = require __DIR__ . '/../config.php';
        $min = $config['payments']['sbp']['min_amount'];
        $max = $config['payments']['sbp']['max_amount'];
        if ($amount < $min || $amount > $max) {
            json_response(['error' => 'Сумма вне лимитов.'], 400);
        }
        db()->prepare('INSERT INTO payments (user_id, amount, status, provider) VALUES (?, ?, "paid", "sbp")')
            ->execute([$user['id'], $amount]);
        db()->prepare('INSERT INTO balances (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
            ->execute([$user['id'], $amount]);
        json_response(['balance' => $balance + $amount, 'message' => t('balance_updated')]);
    }
    if ($action === 'withdraw') {
        $config = require __DIR__ . '/../config.php';
        $limit = $config['security']['rate_limit']['withdrawal'];
        if (rate_limited('withdrawal', $limit['window'], $limit['max'])) {
            json_response(['error' => 'Слишком много заявок.'], 429);
        }
        if ($amount <= 0) {
            json_response(['error' => 'Сумма некорректна.'], 400);
        }
        if ($balance < $amount) {
            json_response(['error' => t('insufficient_funds')], 400);
        }
        db()->prepare('INSERT INTO withdrawals (user_id, amount, status, details) VALUES (?, ?, "pending", ?)')
            ->execute([$user['id'], $amount, $input['details'] ?? '']);
        db()->prepare('UPDATE balances SET balance = balance - ? WHERE user_id = ?')
            ->execute([$amount, $user['id']]);
        json_response(['balance' => $balance - $amount]);
    }
}
json_response(['balance' => user_balance((int) $user['id'])]);
