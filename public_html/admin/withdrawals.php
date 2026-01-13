<?php
require __DIR__ . '/layout.php';
require_staff('withdrawals');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db()->prepare('UPDATE withdrawals SET status = ? WHERE id = ?')
        ->execute([$_POST['status'] ?? 'pending', $_POST['withdrawal_id'] ?? 0]);
}
$rows = db()->query('SELECT w.id, u.email, w.amount, w.status FROM withdrawals w JOIN users u ON u.id = w.user_id ORDER BY w.id DESC LIMIT 50')->fetchAll();
admin_header('Выводы');
?>
<div class="section">
    <h2>Выводы</h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card">
                <p>#<?php echo $row['id']; ?> • <?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?> • <?php echo $row['amount']; ?>₽</p>
                <form method="post">
                    <input type="hidden" name="withdrawal_id" value="<?php echo $row['id']; ?>">
                    <select name="status">
                        <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>pending</option>
                        <option value="on_hold" <?php echo $row['status'] === 'on_hold' ? 'selected' : ''; ?>>on_hold</option>
                        <option value="approved" <?php echo $row['status'] === 'approved' ? 'selected' : ''; ?>>approved</option>
                        <option value="rejected" <?php echo $row['status'] === 'rejected' ? 'selected' : ''; ?>>rejected</option>
                    </select>
                    <button class="btn" type="submit">Сохранить</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
