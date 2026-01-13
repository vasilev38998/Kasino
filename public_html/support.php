<?php
require __DIR__ . '/helpers.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } else {
        db()->prepare('INSERT INTO support_tickets (user_id, subject, message, status) VALUES (?, ?, ?, "open")')
            ->execute([$_SESSION['user_id'] ?? null, $_POST['subject'] ?? '', $_POST['message'] ?? '']);
        $message = 'Запрос отправлен.';
    }
}
render_header(t('support_title'));
?>
<section class="section">
    <h2><?php echo t('support_title'); ?></h2>
    <p><?php echo t('support_hint'); ?></p>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form class="form-card" method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label>Тема</label>
        <input type="text" name="subject" required>
        <label>Сообщение</label>
        <textarea name="message" rows="5" required></textarea>
        <button class="btn" type="submit">Отправить</button>
    </form>
</section>
<?php render_footer(); ?>
