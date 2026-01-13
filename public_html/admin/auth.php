<?php
require __DIR__ . '/../helpers.php';

function staff_user(): ?array
{
    if (empty($_SESSION['staff_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM staff_users WHERE id = ?');
    $stmt->execute([$_SESSION['staff_id']]);
    return $stmt->fetch() ?: null;
}

function staff_has_permission(string $permission): bool
{
    $staff = staff_user();
    if (!$staff) {
        return false;
    }
    if ($staff['role'] === 'admin') {
        return true;
    }
    $stmt = db()->prepare('SELECT COUNT(*) AS total FROM staff_permissions WHERE staff_id = ? AND permission = ?');
    $stmt->execute([$staff['id'], $permission]);
    $row = $stmt->fetch();
    return (int) $row['total'] > 0;
}

function require_staff(?string $permission = null): void
{
    if (!staff_user()) {
        header('Location: /admin/index.php');
        exit;
    }
    if ($permission && !staff_has_permission($permission)) {
        echo '<p>Недостаточно прав.</p>';
        exit;
    }
}

function staff_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM staff_users WHERE email = ?');
    $stmt->execute([$email]);
    $staff = $stmt->fetch();
    if ($staff && password_verify($password, $staff['password_hash'])) {
        $_SESSION['staff_id'] = $staff['id'];
        return true;
    }
    return false;
}
