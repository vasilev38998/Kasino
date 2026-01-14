<?php
require __DIR__ . '/layout.php';
require_staff('support');
$message = '';
$status = $_GET['status'] ?? 'all';
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
$statusOptions = ['all', 'open', 'answered', 'closed'];
if (!in_array($status, $statusOptions, true)) {
    $status = 'all';
}
$filterSql = '';
$params = [];
if ($status !== 'all') {
    $filterSql = 'WHERE st.status = ?';
    $params[] = $status;
}
$stmt = db()->prepare("SELECT st.id, st.user_id, st.subject, st.message, st.status, st.created_at, st.reply_message, st.replied_at, u.email FROM support_tickets st LEFT JOIN users u ON u.id = st.user_id {$filterSql} ORDER BY st.id DESC LIMIT 50");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$counts = db()->query('SELECT status, COUNT(*) AS total FROM support_tickets GROUP BY status')->fetchAll();
$countMap = ['open' => 0, 'answered' => 0, 'closed' => 0];
foreach ($counts as $count) {
    $countMap[$count['status']] = (int) $count['total'];
}
admin_header('Поддержка');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Поддержка</h2>
            <p class="muted">Отвечайте на тикеты и следите за статусами.</p>
        </div>
        <div class="admin-actions">
            <span class="admin-pill is-info">Открытые: <?php echo $countMap['open']; ?></span>
            <span class="admin-pill is-success">Отвеченные: <?php echo $countMap['answered']; ?></span>
            <span class="admin-pill">Закрытые: <?php echo $countMap['closed']; ?></span>
        </div>
    </div>
    <?php if ($message): ?>
        <div class="admin-alert"><?php echo $message; ?></div>
    <?php endif; ?>
    <div class="admin-filters">
        <a class="admin-filter<?php echo $status === 'all' ? ' is-active' : ''; ?>" href="/admin/support.php">Все</a>
        <a class="admin-filter<?php echo $status === 'open' ? ' is-active' : ''; ?>" href="/admin/support.php?status=open">Открытые</a>
        <a class="admin-filter<?php echo $status === 'answered' ? ' is-active' : ''; ?>" href="/admin/support.php?status=answered">Отвеченные</a>
        <a class="admin-filter<?php echo $status === 'closed' ? ' is-active' : ''; ?>" href="/admin/support.php?status=closed">Закрытые</a>
    </div>
    <div class="admin-support-grid">
        <?php foreach ($rows as $row): ?>
            <div class="card admin-support-card">
                <div class="admin-support-header">
                    <div>
                        <strong><?php echo htmlspecialchars($row['subject'], ENT_QUOTES); ?></strong>
                        <div class="muted small">#<?php echo $row['id']; ?> • <?php echo $row['email'] ?: 'Гость'; ?></div>
                    </div>
                    <span class="admin-pill <?php echo $row['status'] === 'open' ? 'is-info' : ($row['status'] === 'answered' ? 'is-success' : ''); ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </div>
                <p><?php echo htmlspecialchars($row['message'], ENT_QUOTES); ?></p>
                <p class="muted small"><?php echo $row['created_at']; ?></p>
                <?php if ($row['reply_message']): ?>
                    <div class="admin-support-reply">
                        <strong>Ответ</strong>
                        <p><?php echo htmlspecialchars($row['reply_message'], ENT_QUOTES); ?></p>
                        <p class="muted small"><?php echo $row['replied_at']; ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($row['user_id']): ?>
                    <form class="admin-form" method="post">
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
</section>
<?php admin_footer(); ?>
