<?php
require __DIR__ . '/helpers.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = t('security_error');
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = t('invalid_email');
        } else {
            $stmt = db()->prepare('SELECT id, email FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                issue_password_reset((int) $user['id'], $user['email']);
            }
            $message = t('password_reset_sent');
        }
    }
}

render_header(t('forgot_password_title'));
?>
<div class="form-card">
    <h2><?php echo t('forgot_password_title'); ?></h2>
    <p class="muted"><?php echo t('forgot_password_hint'); ?></p>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label><?php echo t('email'); ?></label>
        <input type="email" name="email" required>
        <button class="btn" type="submit"><?php echo t('send_code'); ?></button>
    </form>
    <a class="muted small" href="/reset_password.php"><?php echo t('have_reset_code'); ?></a>
</div>
<?php render_footer(); ?>
