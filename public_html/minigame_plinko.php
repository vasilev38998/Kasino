<?php
require __DIR__ . '/helpers.php';
require_login();
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
render_header(t('plinko_title'));
?>
<section class="section">
    <h2><?php echo t('plinko_title'); ?></h2>
    <div class="minigame-layout" data-plinko-game data-plinko-label="<?php echo t('plinko_result'); ?>">
        <div class="card plinko-panel">
            <canvas class="plinko-canvas" width="420" height="520"></canvas>
            <div class="plinko-result" data-plinko-result><?php echo t('plinko_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <button class="btn minigame-play" data-minigame="plinko"><?php echo t('play_now'); ?></button>
            <div class="plinko-multipliers">
                <span>x0</span>
                <span>x0.5</span>
                <span>x1</span>
                <span>x1.5</span>
                <span>x2</span>
                <span>x3</span>
                <span>x5</span>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
