<?php
require __DIR__ . '/layout.php';
require_staff('notifications');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (NULL, ?, ?, ?)')
        ->execute([$_POST['title'] ?? '', $_POST['message'] ?? '', $_POST['type'] ?? 'info']);
}
$rows = db()->query('SELECT title, message, type, created_at FROM notifications ORDER BY id DESC LIMIT 50')->fetchAll();
admin_header('Уведомления');
?>
<div class="section">
    <h2>Уведомления</h2>
    <form class="form-card" method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label>Заголовок</label>
        <input type="text" name="title" required>
        <label>Сообщение</label>
        <textarea name="message" rows="4" required></textarea>
        <label>Тип</label>
        <select name="type">
            <option value="info">info</option>
            <option value="bonus">bonus</option>
            <option value="win">win</option>
        </select>
        <button class="btn" type="submit">Отправить</button>
    </form>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo $row['type']; ?> • <?php echo $row['title']; ?> • <?php echo $row['created_at']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
