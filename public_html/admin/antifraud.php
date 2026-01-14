<?php
require __DIR__ . '/layout.php';
require_staff('antifraud');
$rows = db()->query('SELECT u.email, r.risk_score, r.flags FROM user_risk r JOIN users u ON u.id = r.user_id ORDER BY r.risk_score DESC LIMIT 50')->fetchAll();
admin_header('Антифрод');
?>
<div class="section">
    <h2>Антифрод</h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?> • Risk <?php echo $row['risk_score']; ?> • <?php echo $row['flags']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
