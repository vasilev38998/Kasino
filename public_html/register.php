<?php
require __DIR__ . '/helpers.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } elseif (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $message = 'Некорректный email.';
    } elseif (strlen(trim($_POST['nickname'] ?? '')) < 3) {
        $message = 'Никнейм должен быть не короче 3 символов.';
    } elseif (strlen(trim($_POST['nickname'] ?? '')) > 50) {
        $message = 'Никнейм слишком длинный.';
    } elseif (empty($_POST['birth_date'])) {
        $message = 'Укажите дату рождения.';
    } elseif (strlen($_POST['password'] ?? '') < 8) {
        $message = 'Пароль должен быть не короче 8 символов.';
    } elseif (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
        $message = 'Пароли не совпадают.';
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? OR nickname = ?');
        $stmt->execute([$_POST['email'], $_POST['nickname']]);
        if ($stmt->fetch()) {
            $message = 'Email или никнейм уже зарегистрирован.';
        } else {
            $stmt = db()->prepare('INSERT INTO users (email, nickname, birth_date, password_hash, status, language) VALUES (?, ?, ?, ?, "active", ?)');
            $stmt->execute([
                $_POST['email'],
                trim($_POST['nickname']),
                $_POST['birth_date'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                lang(),
            ]);
            $userId = (int) db()->lastInsertId();
            db()->prepare('INSERT INTO balances (user_id, balance) VALUES (?, 0)')->execute([$userId]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $message = t('registration_success');
            header('Location: /profile.php');
            exit;
        }
    }
}
render_header(t('register_title'));
?>
<div class="form-card">
    <h2><?php echo t('register_title'); ?></h2>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label><?php echo t('email'); ?></label>
        <input type="email" name="email" required>
        <label><?php echo t('nickname'); ?></label>
        <input type="text" name="nickname" required>
        <label><?php echo t('birth_date'); ?></label>
        <input type="date" name="birth_date" required>
        <label><?php echo t('password'); ?></label>
        <input type="password" name="password" required>
        <label><?php echo t('confirm_password'); ?></label>
        <input type="password" name="password_confirm" required>
        <button class="btn" type="submit"><?php echo t('submit_register'); ?></button>
    </form>
</div>
<?php render_footer(); ?>
