<?php
require __DIR__ . '/layout.php';
require_staff('slots');
$slots = slots_catalog();
admin_header('Слоты');
?>
<div class="section">
    <h2>Слоты</h2>
    <div class="cards">
        <?php foreach ($slots as $slot): ?>
            <div class="card">
                <strong><?php echo $slot['name']; ?></strong>
                <p>RTP: <?php echo $slot['rtp']; ?>%</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
