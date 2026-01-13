<?php
require __DIR__ . '/helpers.php';
render_header(t('promotions_title'));
?>
<section class="section">
    <h2><?php echo site_setting('promotions_title', t('promotions_title')); ?></h2>
    <div class="cards">
        <div class="card promo-card">
            <strong><?php echo site_setting('promo_vip_title', t('promo_vip_title')); ?></strong>
            <p><?php echo site_setting('promo_vip_desc', t('promo_vip_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 55%"></div>
            </div>
            <div class="mission-meta">
                <span>55% • осталось 2 дня</span>
                <span>50 000₽</span>
            </div>
        </div>
        <div class="card promo-card">
            <strong><?php echo site_setting('promo_streak_title', t('promo_streak_title')); ?></strong>
            <p><?php echo site_setting('promo_streak_desc', t('promo_streak_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 30%"></div>
            </div>
            <div class="mission-meta">
                <span>3 / 10</span>
                <span>+15 FS</span>
            </div>
        </div>
        <div class="card promo-card">
            <strong><?php echo site_setting('promo_codes_title', t('promo_codes_title')); ?></strong>
            <p><?php echo site_setting('promo_codes_desc', t('promo_codes_desc')); ?></p>
            <div class="promo-tags">
                <?php
                $codes = array_filter(array_map('trim', explode(',', site_setting('promo_codes_list', 'LUX100, NEON50, WEEKEND'))));
                foreach ($codes as $code):
                ?>
                    <span class="tag"><?php echo htmlspecialchars($code, ENT_QUOTES); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
