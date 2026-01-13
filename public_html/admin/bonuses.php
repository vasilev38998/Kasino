<?php
require __DIR__ . '/layout.php';
require_staff('bonuses');
$rows = db()->query('SELECT u.email, b.type, b.amount, b.expires_at FROM bonuses b JOIN users u ON u.id = b.user_id ORDER BY b.id DESC LIMIT 50')->fetchAll();
admin_header('Бонусы');
?>
<div class="section">
    <h2>Бонусы</h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?> • <?php echo $row['type']; ?> • <?php echo $row['amount']; ?>₽</div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
