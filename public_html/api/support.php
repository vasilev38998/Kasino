<?php
require __DIR__ . '/../helpers.php';
$user = current_user();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}

db()->prepare('INSERT INTO support_tickets (user_id, subject, message, status) VALUES (?, ?, ?, "open")')
    ->execute([$user['id'], $input['subject'] ?? '', $input['message'] ?? '']);

json_response(['success' => true]);
