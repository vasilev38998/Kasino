<?php
require __DIR__ . '/auth.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } elseif (staff_login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /admin/users.php');
        exit;
    } else {
        $message = 'Неверные учетные данные.';
    }
}
$staff = staff_user();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/style.css">
    <title>Админ-панель</title>
</head>
<body class="admin-body admin-login">
<div class="admin-login-shell">
    <div class="admin-login-card">
        <div class="admin-brand">Kasino <span>Admin</span></div>
        <h2>Админ-панель</h2>
        <p class="muted">Управляйте пользователями, платежами и контентом.</p>
        <?php if ($staff): ?>
            <div class="admin-alert">Добро пожаловать, <?php echo htmlspecialchars($staff['email'], ENT_QUOTES); ?></div>
            <a class="btn" href="/admin/users.php">Перейти в панель</a>
        <?php else: ?>
            <?php if ($message): ?><div class="admin-alert"><?php echo $message; ?></div><?php endif; ?>
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Пароль</label>
                <input type="password" name="password" required>
                <button class="btn" type="submit">Войти</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
