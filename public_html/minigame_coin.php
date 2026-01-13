<?php
require __DIR__ . '/helpers.php';
require_login();
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
render_header(t('coin_title'));
?>
<section class="section">
    <h2><?php echo t('coin_title'); ?></h2>
    <div class="minigame-layout" data-coin-game data-coin-win="<?php echo t('coin_win'); ?>" data-coin-lose="<?php echo t('coin_lose'); ?>" data-heads="<?php echo t('heads'); ?>" data-tails="<?php echo t('tails'); ?>">
        <div class="card coin-panel">
            <div class="coin-display" data-coin-display>
                <div class="coin-face coin-front"><?php echo t('heads'); ?></div>
                <div class="coin-face coin-back"><?php echo t('tails'); ?></div>
            </div>
            <div class="coin-result" data-coin-result><?php echo t('coin_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <label><?php echo t('choose_side'); ?></label>
            <select class="minigame-side">
                <option value="heads"><?php echo t('heads'); ?></option>
                <option value="tails"><?php echo t('tails'); ?></option>
            </select>
            <button class="btn minigame-play" data-minigame="coin"><?php echo t('play_now'); ?></button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
