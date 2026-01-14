<?php
require __DIR__ . '/layout.php';
require_staff('withdrawals');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    db()->prepare('UPDATE withdrawals SET status = ? WHERE id = ?')
        ->execute([$_POST['status'] ?? 'pending', $_POST['withdrawal_id'] ?? 0]);
}
$rows = db()->query('SELECT w.id, u.email, w.amount, w.status FROM withdrawals w JOIN users u ON u.id = w.user_id ORDER BY w.id DESC LIMIT 50')->fetchAll();
admin_header('Выводы');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Выводы</h2>
            <p class="muted">Контроль заявок на вывод средств.</p>
        </div>
        <div class="admin-actions">
            <span class="admin-pill"><?php echo count($rows); ?> заявок</span>
        </div>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>ID</div>
            <div>Пользователь</div>
            <div>Сумма</div>
            <div>Статус</div>
            <div>Действия</div>
        </div>
        <?php foreach ($rows as $row): ?>
            <form class="admin-table-row" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="withdrawal_id" value="<?php echo $row['id']; ?>">
                <div class="muted">#<?php echo $row['id']; ?></div>
                <div><?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?></div>
                <div><?php echo number_format((float) $row['amount'], 2, '.', ' '); ?>₽</div>
                <div>
                    <select name="status">
                        <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>pending</option>
                        <option value="on_hold" <?php echo $row['status'] === 'on_hold' ? 'selected' : ''; ?>>on_hold</option>
                        <option value="approved" <?php echo $row['status'] === 'approved' ? 'selected' : ''; ?>>approved</option>
                        <option value="rejected" <?php echo $row['status'] === 'rejected' ? 'selected' : ''; ?>>rejected</option>
                    </select>
                </div>
                <div>
                    <button class="btn" type="submit">Сохранить</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
