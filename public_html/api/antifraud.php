<?php
require __DIR__ . '/../helpers.php';
$user = current_user();
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$event = $input['event'] ?? 'unknown';
$detail = $input['detail'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

db()->prepare('INSERT INTO antifraud_events (user_id, event, detail, ip, user_agent) VALUES (?, ?, ?, ?, ?)')
    ->execute([$user['id'], $event, $detail, $ip, $userAgent]);

json_response(['success' => true]);
