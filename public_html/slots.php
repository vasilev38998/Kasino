<?php
require __DIR__ . '/helpers.php';
render_header(t('slots_title'));
$slots = slots_catalog();
?>
<section class="section">
    <h2><?php echo t('slots_title'); ?></h2>
    <div class="cards">
        <?php foreach ($slots as $slot): ?>
            <div class="card slot-card">
                <img src="<?php echo $slot['icon']; ?>" alt="<?php echo $slot['name']; ?>">
                <strong><?php echo $slot['name']; ?></strong>
                <span>RTP <?php echo $slot['rtp']; ?>%</span>
                <a class="btn" href="/slot.php?game=<?php echo $slot['slug']; ?>"><?php echo t('slot_play'); ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
