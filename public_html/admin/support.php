<?php
require __DIR__ . '/layout.php';
require_staff('support');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'reply') {
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $reply = trim($_POST['reply'] ?? '');
        if ($reply === '') {
            $message = 'Введите ответ.';
        } else {
            $stmt = db()->prepare('SELECT user_id, subject FROM support_tickets WHERE id = ?');
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                $message = 'Запрос не найден.';
            } elseif (!$ticket['user_id']) {
                $message = 'Запрос от гостя, нельзя отправить уведомление.';
            } else {
                db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)')
                    ->execute([$ticket['user_id'], 'Ответ поддержки: ' . $ticket['subject'], $reply, 'support']);
                db()->prepare('UPDATE support_tickets SET status = "answered", reply_message = ?, replied_at = NOW() WHERE id = ?')
                    ->execute([$reply, $ticketId]);
                $message = 'Ответ отправлен.';
            }
        }
    }
    if ($action === 'close') {
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        db()->prepare('UPDATE support_tickets SET status = "closed" WHERE id = ?')
            ->execute([$ticketId]);
        $message = 'Запрос закрыт.';
    }
}
$rows = db()->query('SELECT st.id, st.user_id, st.subject, st.message, st.status, st.created_at, st.reply_message, st.replied_at, u.email FROM support_tickets st LEFT JOIN users u ON u.id = st.user_id ORDER BY st.id DESC LIMIT 50')->fetchAll();
admin_header('Поддержка');
?>
<div class="section">
    <h2>Поддержка</h2>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card">
                <strong><?php echo htmlspecialchars($row['subject'], ENT_QUOTES); ?></strong>
                <p><?php echo htmlspecialchars($row['message'], ENT_QUOTES); ?></p>
                <p class="muted small"><?php echo $row['created_at']; ?> • статус: <?php echo $row['status']; ?> • <?php echo $row['email'] ?: 'Гость'; ?></p>
                <?php if ($row['reply_message']): ?>
                    <div class="card">
                        <strong>Ответ</strong>
                        <p><?php echo htmlspecialchars($row['reply_message'], ENT_QUOTES); ?></p>
                        <p class="muted small"><?php echo $row['replied_at']; ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($row['user_id']): ?>
                    <form class="form-card" method="post">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                        <label>Ответ пользователю</label>
                        <textarea name="reply" rows="3" required></textarea>
                        <button class="btn" type="submit">Отправить ответ</button>
                    </form>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="close">
                    <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                    <button class="btn ghost" type="submit">Закрыть</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
