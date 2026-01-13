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
            <div class="plinko-slots" data-plinko-slots></div>
            <div class="plinko-result" data-plinko-result><?php echo t('plinko_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('plinko_difficulty'); ?></label>
            <div class="plinko-difficulty" role="radiogroup" aria-label="<?php echo t('plinko_difficulty'); ?>">
                <label class="plinko-option">
                    <input type="radio" name="plinko_difficulty" value="easy" checked>
                    <span><?php echo t('plinko_easy'); ?></span>
                </label>
                <label class="plinko-option">
                    <input type="radio" name="plinko_difficulty" value="medium">
                    <span><?php echo t('plinko_medium'); ?></span>
                </label>
                <label class="plinko-option">
                    <input type="radio" name="plinko_difficulty" value="hard">
                    <span><?php echo t('plinko_hard'); ?></span>
                </label>
            </div>
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <button class="btn minigame-play" data-minigame="plinko"><?php echo t('play_now'); ?></button>
            <p class="muted plinko-hint"><?php echo t('plinko_hint'); ?></p>
        </div>
    </div>
</section>
<?php render_footer(); ?>
