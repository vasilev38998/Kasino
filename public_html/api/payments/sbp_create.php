<?php
require __DIR__ . '/../../helpers.php';
$user = current_user();
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$config = require __DIR__ . '/../../config.php';
$amount = (float) ($input['amount'] ?? 0);
if ($amount < $config['payments']['sbp']['min_amount'] || $amount > $config['payments']['sbp']['max_amount']) {
    json_response(['error' => 'Сумма вне лимитов.'], 400);
}
$reference = bin2hex(random_bytes(8));
$db = db();
$db->prepare('INSERT INTO payments (user_id, amount, status, provider, reference) VALUES (?, ?, "pending", "sbp", ?)')
    ->execute([$user['id'], $amount, $reference]);
$payUrl = 'https://sbp.example/qr/' . $reference;
json_response(['reference' => $reference, 'pay_url' => $payUrl]);
