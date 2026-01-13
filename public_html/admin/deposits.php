<?php
require __DIR__ . '/layout.php';
require_staff('deposits');
$rows = db()->query('SELECT p.id, u.email, p.amount, p.status, p.created_at FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 50')->fetchAll();
admin_header('Депозиты');
?>
<div class="section">
    <h2>Депозиты</h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card">#<?php echo $row['id']; ?> • <?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?> • <?php echo $row['amount']; ?>₽ • <?php echo $row['status']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
