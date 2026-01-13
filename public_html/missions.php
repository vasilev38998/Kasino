<?php
require __DIR__ . '/helpers.php';
render_header(t('missions_title'));
?>
<section class="section">
    <h2><?php echo site_setting('missions_title', t('missions_title')); ?></h2>
    <p class="muted"><?php echo site_setting('missions_subtitle', t('missions_subtitle')); ?></p>
    <div class="cards missions-grid">
        <div class="card mission-card">
            <strong><?php echo site_setting('mission_daily_spin', t('mission_daily_spin')); ?></strong>
            <p><?php echo site_setting('mission_daily_spin_desc', t('mission_daily_spin_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 40%"></div>
            </div>
            <div class="mission-meta">
                <span>4 / 10</span>
                <span>+50₽</span>
            </div>
        </div>
        <div class="card mission-card">
            <strong><?php echo site_setting('mission_weekly_slots', t('mission_weekly_slots')); ?></strong>
            <p><?php echo site_setting('mission_weekly_slots_desc', t('mission_weekly_slots_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 65%"></div>
            </div>
            <div class="mission-meta">
                <span>13 / 20</span>
                <span>+300₽</span>
            </div>
        </div>
        <div class="card mission-card">
            <strong><?php echo site_setting('mission_minigame', t('mission_minigame')); ?></strong>
            <p><?php echo site_setting('mission_minigame_desc', t('mission_minigame_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 20%"></div>
            </div>
            <div class="mission-meta">
                <span>1 / 5</span>
                <span>+120₽</span>
            </div>
        </div>
    </div>
</section>
<section class="section">
    <h2><?php echo site_setting('referral_title', t('referral_title')); ?></h2>
    <div class="card referral-card">
        <div>
            <strong><?php echo site_setting('referral_reward', t('referral_reward')); ?></strong>
            <p class="muted"><?php echo site_setting('referral_desc', t('referral_desc')); ?></p>
        </div>
        <div class="referral-code">
            <span>VIP-7KX9</span>
            <button class="btn ghost" type="button"><?php echo t('referral_copy'); ?></button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
