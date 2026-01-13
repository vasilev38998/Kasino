<?php
require __DIR__ . '/../../helpers.php';
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$config = require __DIR__ . '/../../config.php';
$reference = $payload['reference'] ?? '';
$amount = (float) ($payload['amount'] ?? 0);
$signature = $payload['signature'] ?? '';
$expected = hash_hmac('sha256', $reference . '|' . $amount, $config['payments']['sbp']['secret']);
if (!hash_equals($expected, $signature)) {
    json_response(['error' => 'Неверная подпись.'], 403);
}
$db = db();
$db->beginTransaction();
$stmt = $db->prepare('SELECT * FROM payments WHERE reference = ? FOR UPDATE');
$stmt->execute([$reference]);
$payment = $stmt->fetch();
if (!$payment) {
    $db->rollBack();
    json_response(['error' => 'Платеж не найден.'], 404);
}
if ($payment['status'] === 'paid') {
    $db->commit();
    json_response(['status' => 'already_paid']);
}
$db->prepare('UPDATE payments SET status = "paid" WHERE id = ?')
    ->execute([$payment['id']]);
$db->prepare('INSERT INTO balances (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
    ->execute([$payment['user_id'], $amount]);
$db->commit();
json_response(['status' => 'paid']);
