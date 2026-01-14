<?php
require __DIR__ . '/helpers.php';

$message = '';
$email = $_SESSION['pending_email'] ?? ($_GET['email'] ?? '');
$email = trim((string) $email);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = t('security_error');
    } else {
        $action = $_POST['action'] ?? 'verify';
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = t('invalid_email');
        } else {
            $stmt = db()->prepare('SELECT id, email_verified_at, email_verification_code, email_verification_expires_at FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user) {
                $message = t('email_not_found');
            } elseif (!empty($user['email_verified_at'])) {
                $message = t('email_already_verified');
            } elseif ($action === 'resend') {
                issue_email_verification((int) $user['id'], $email);
                $message = t('verification_sent');
            } else {
                $code = trim($_POST['code'] ?? '');
                $hash = $user['email_verification_code'] ?? '';
                $expires = $user['email_verification_expires_at'] ?? null;
                $expired = !$expires || strtotime($expires) < time();
                if ($code === '' || !$hash || $expired || !hash_equals($hash, hash_one_time_code($code))) {
                    $message = t('verification_invalid');
                } else {
                    db()->prepare('UPDATE users SET email_verified_at = NOW(), email_verification_code = NULL, email_verification_expires_at = NULL WHERE id = ?')
                        ->execute([(int) $user['id']]);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    unset($_SESSION['pending_email']);
                    header('Location: /profile.php');
                    exit;
                }
            }
        }
    }
}

render_header(t('verify_email_title'));
?>
<div class="form-card">
    <h2><?php echo t('verify_email_title'); ?></h2>
    <p class="muted"><?php echo t('verify_email_hint'); ?></p>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="verify">
        <label><?php echo t('email'); ?></label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">
        <label><?php echo t('verification_code'); ?></label>
        <input type="text" name="code" inputmode="numeric" minlength="6" maxlength="6" required>
        <button class="btn" type="submit"><?php echo t('verify_email_submit'); ?></button>
    </form>
    <form method="post" class="form-card" style="margin-top: 16px;">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="resend">
        <label><?php echo t('email'); ?></label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">
        <button class="btn ghost" type="submit"><?php echo t('resend_code'); ?></button>
    </form>
</div>
<?php render_footer(); ?>
