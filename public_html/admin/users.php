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
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Пользователи</h2>
            <p class="muted">Последние 50 регистраций. Меняйте статус прямо в списке.</p>
        </div>
        <div class="admin-actions">
            <div class="admin-search admin-search-inline">
                <input type="search" placeholder="Поиск по email или никнейму">
                <button class="btn ghost" type="button">Найти</button>
            </div>
        </div>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>ID</div>
            <div>Email</div>
            <div>Никнейм</div>
            <div>Статус</div>
            <div>Дата</div>
            <div>Действия</div>
        </div>
        <?php foreach ($users as $user): ?>
            <div class="admin-table-row">
                <div class="muted">#<?php echo $user['id']; ?></div>
                <div><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></div>
                <div><?php echo htmlspecialchars($user['nickname'], ENT_QUOTES); ?></div>
                <div>
                    <span class="admin-pill <?php echo $user['status'] === 'banned' ? 'is-danger' : 'is-success'; ?>">
                        <?php echo $user['status']; ?>
                    </span>
                </div>
                <div class="muted"><?php echo $user['created_at']; ?></div>
                <div>
                    <form class="admin-inline-form" method="post">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <select name="status">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>active</option>
                            <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>banned</option>
                        </select>
                        <button class="btn" type="submit">Сохранить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
