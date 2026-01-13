<?php
require __DIR__ . '/helpers.php';
render_header(t('minigames_title'));
?>
<section class="section">
    <h2><?php echo t('minigames_title'); ?></h2>
    <p class="muted"><?php echo t('minigames_subtitle'); ?></p>
    <div class="cards minigames-grid">
        <div class="card minigame-card">
            <div class="minigame-icon coin"></div>
            <h3><?php echo t('coin_title'); ?></h3>
            <p><?php echo t('coin_pick'); ?></p>
            <a class="btn" href="/minigame_coin.php"><?php echo t('play_now'); ?></a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon plinko"></div>
            <h3><?php echo t('plinko_title'); ?></h3>
            <p><?php echo t('plinko_subtitle'); ?></p>
            <a class="btn" href="/minigame_plinko.php"><?php echo t('play_now'); ?></a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon dice"></div>
            <h3><?php echo t('dice_title'); ?></h3>
            <p><?php echo t('dice_subtitle'); ?></p>
            <a class="btn" href="/minigame_dice.php"><?php echo t('play_now'); ?></a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon highlow"></div>
            <h3><?php echo t('highlow_title'); ?></h3>
            <p><?php echo t('highlow_subtitle'); ?></p>
            <a class="btn" href="/minigame_highlow.php"><?php echo t('play_now'); ?></a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon treasure"></div>
            <h3><?php echo t('treasure_title'); ?></h3>
            <p><?php echo t('treasure_subtitle'); ?></p>
            <a class="btn" href="/minigame_treasure.php"><?php echo t('play_now'); ?></a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon wheel"></div>
            <h3><?php echo t('wheel_title'); ?></h3>
            <p><?php echo t('wheel_subtitle'); ?></p>
            <a class="btn" href="/minigame_wheel.php"><?php echo t('play_now'); ?></a>
        </div>
    </div>
</section>
<?php render_footer(); ?>
