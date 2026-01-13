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
<body>
<div class="form-card">
    <h2>Админ-панель</h2>
    <?php if ($staff): ?>
        <p>Добро пожаловать, <?php echo htmlspecialchars($staff['email'], ENT_QUOTES); ?></p>
        <a class="btn" href="/admin/users.php">Перейти в панель</a>
    <?php else: ?>
        <?php if ($message): ?><p><?php echo $message; ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Пароль</label>
            <input type="password" name="password" required>
            <button class="btn" type="submit">Войти</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
