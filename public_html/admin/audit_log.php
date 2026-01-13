<?php
require __DIR__ . '/layout.php';
require_staff('audit');
$rows = db()->query('SELECT id, action, created_at FROM audit_log ORDER BY id DESC LIMIT 50')->fetchAll();
admin_header('Аудит');
?>
<div class="section">
    <h2>Журнал действий</h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card">#<?php echo $row['id']; ?> • <?php echo $row['action']; ?> • <?php echo $row['created_at']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
