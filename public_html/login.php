<?php
require __DIR__ . '/helpers.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } else {
        $config = require __DIR__ . '/config.php';
        $limit = $config['security']['rate_limit']['login'];
        if (rate_limited('login', $limit['window'], $limit['max'])) {
            $message = 'Слишком много попыток входа.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$_POST['email'] ?? '']);
            $user = $stmt->fetch();
            $success = $user && password_verify($_POST['password'] ?? '', $user['password_hash']);
            db()->prepare('INSERT INTO login_attempts (user_id, ip, success) VALUES (?, ?, ?)')
                ->execute([$user['id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? '', $success ? 1 : 0]);
            if ($success) {
                if (empty($user['email_verified_at'])) {
                    issue_email_verification((int) $user['id'], $user['email']);
                    $_SESSION['pending_email'] = $user['email'];
                    header('Location: /verify_email.php');
                    exit;
                }
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                header('Location: /profile.php');
                exit;
            }
            $message = t('invalid_credentials');
        }
    }
}
render_header(t('login_title'));
?>
<div class="form-card">
    <h2><?php echo t('login_title'); ?></h2>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label><?php echo t('email'); ?></label>
        <input type="email" name="email" required>
        <label><?php echo t('password'); ?></label>
        <input type="password" name="password" required>
        <button class="btn" type="submit"><?php echo t('submit_login'); ?></button>
        <a class="muted small" href="/forgot_password.php"><?php echo t('forgot_password_link'); ?></a>
    </form>
</div>
<?php render_footer(); ?>
