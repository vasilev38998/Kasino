<?php
require __DIR__ . '/layout.php';
require_staff('staff');
$staff = db()->query('SELECT id, email, role FROM staff_users ORDER BY id DESC')->fetchAll();
admin_header('Персонал');
?>
<div class="section">
    <h2>Персонал</h2>
    <div class="cards">
        <?php foreach ($staff as $member): ?>
            <div class="card">
                <p><?php echo htmlspecialchars($member['email'], ENT_QUOTES); ?></p>
                <p>Роль: <?php echo $member['role']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
