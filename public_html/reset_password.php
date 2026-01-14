<?php
require __DIR__ . '/helpers.php';

$message = '';
$email = trim($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = t('security_error');
    } else {
        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = t('invalid_email');
        } elseif ($code === '') {
            $message = t('verification_invalid');
        } elseif (strlen($password) < 8) {
            $message = t('password_too_short');
        } elseif ($password !== $confirm) {
            $message = t('passwords_not_match');
        } else {
            $stmt = db()->prepare('SELECT id, password_reset_code, password_reset_expires_at FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $hash = $user['password_reset_code'] ?? '';
            $expires = $user['password_reset_expires_at'] ?? null;
            $expired = !$expires || strtotime($expires) < time();
            if (!$user || !$hash || $expired || !hash_equals($hash, hash_one_time_code($code))) {
                $message = t('verification_invalid');
            } else {
                db()->prepare('UPDATE users SET password_hash = ?, password_reset_code = NULL, password_reset_expires_at = NULL WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
                $message = t('password_reset_success');
            }
        }
    }
}

render_header(t('reset_password_title'));
?>
<div class="form-card">
    <h2><?php echo t('reset_password_title'); ?></h2>
    <p class="muted"><?php echo t('reset_password_hint'); ?></p>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label><?php echo t('email'); ?></label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">
        <label><?php echo t('verification_code'); ?></label>
        <input type="text" name="code" inputmode="numeric" minlength="6" maxlength="6" required>
        <label><?php echo t('password'); ?></label>
        <input type="password" name="password" required>
        <label><?php echo t('confirm_password'); ?></label>
        <input type="password" name="password_confirm" required>
        <button class="btn" type="submit"><?php echo t('reset_password_submit'); ?></button>
    </form>
    <a class="muted small" href="/login.php"><?php echo t('back_to_login'); ?></a>
</div>
<?php render_footer(); ?>
