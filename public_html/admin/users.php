<?php
require __DIR__ . '/layout.php';
require_staff('users');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $stmt = db()->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->execute([$_POST['status'] ?? 'active', $_POST['user_id'] ?? 0]);
}
$users = db()->query('SELECT id, email, nickname, status, created_at FROM users ORDER BY id DESC LIMIT 50')->fetchAll();
admin_header('Пользователи');
?>
<div class="section">
    <h2>Пользователи</h2>
    <div class="cards">
        <?php foreach ($users as $user): ?>
            <div class="card">
                <p><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></p>
                <p>Никнейм: <?php echo htmlspecialchars($user['nickname'], ENT_QUOTES); ?></p>
                <p>Статус: <?php echo $user['status']; ?></p>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <select name="status">
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>active</option>
                        <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>banned</option>
                    </select>
                    <button class="btn" type="submit">Сохранить</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
