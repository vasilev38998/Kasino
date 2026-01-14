<?php
require __DIR__ . '/layout.php';
require_staff('balances');
$rows = db()->query('SELECT u.email, b.balance FROM balances b JOIN users u ON u.id = b.user_id ORDER BY b.balance DESC LIMIT 50')->fetchAll();
admin_header('Балансы');
?>
<div class="section">
    <h2>Балансы</h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?> • <?php echo number_format($row['balance'], 2, '.', ' '); ?>₽</div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
