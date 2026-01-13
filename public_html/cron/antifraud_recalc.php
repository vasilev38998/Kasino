<?php
require __DIR__ . '/../helpers.php';
$users = db()->query('SELECT id FROM users')->fetchAll();
foreach ($users as $user) {
    $attempts = db()->prepare('SELECT COUNT(*) AS total FROM login_attempts WHERE user_id = ? AND created_at > NOW() - INTERVAL 7 DAY');
    $attempts->execute([$user['id']]);
    $total = (int) $attempts->fetch()['total'];
    $risk = min(100, $total * 10);
    db()->prepare('INSERT INTO user_risk (user_id, risk_score, flags) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE risk_score = VALUES(risk_score), flags = VALUES(flags)')
        ->execute([$user['id'], $risk, $risk > 70 ? 'abnormal_login' : 'none']);
}
