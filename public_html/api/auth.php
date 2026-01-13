<?php
require __DIR__ . '/../helpers.php';
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_POST['action'] ?? '');

if ($action === 'lang') {
    set_lang($input['language'] ?? 'ru');
    if (current_user()) {
        db()->prepare('UPDATE users SET language = ? WHERE id = ?')
            ->execute([lang(), $_SESSION['user_id']]);
    }
    json_response(['message' => t('language_updated')]);
}

if ($action === 'login') {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$input['email'] ?? '']);
    $user = $stmt->fetch();
    if ($user && password_verify($input['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        json_response(['success' => true]);
    }
    json_response(['error' => t('invalid_credentials')], 401);
}

if ($action === 'logout') {
    logout();
    json_response(['success' => true]);
}

if ($action === 'social_bind') {
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Требуется авторизация.'], 401);
    }
    $provider = $input['provider'] ?? '';
    $providerId = $input['provider_id'] ?? '';
    if (!in_array($provider, ['vk', 'telegram'], true) || !$providerId) {
        json_response(['error' => 'Неверные данные.'], 400);
    }
    db()->prepare('INSERT INTO social_accounts (user_id, provider, provider_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE provider_id = VALUES(provider_id)')
        ->execute([$user['id'], $provider, $providerId]);
    json_response(['success' => true]);
}

json_response(['error' => 'Неверный запрос.'], 400);
