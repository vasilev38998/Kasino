<?php
require __DIR__ . '/../helpers.php';
$type = $_GET['type'] ?? 'list';
if ($type === 'live') {
    json_response([
        'items' => [
            ['player' => 'Nova', 'slot' => 'Aurora Cascade', 'amount' => 1400],
            ['player' => 'Raven', 'slot' => 'Cosmic Cluster', 'amount' => 980],
            ['player' => 'Luna', 'slot' => 'Dragon Sticky Wilds', 'amount' => 2100],
        ],
    ]);
}
$user = current_user();
if (!$user) {
    json_response(['items' => []]);
}
$stmt = db()->prepare('SELECT title, message, type, created_at FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 20');
$stmt->execute([$user['id']]);
json_response(['items' => $stmt->fetchAll()]);
