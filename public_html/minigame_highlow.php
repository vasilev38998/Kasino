<?php
require __DIR__ . '/helpers.php';
require_login(true);
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
$isDemo = demo_mode_enabled() && !current_user();
render_header(t('highlow_title'));
?>
<section class="section">
    <h2><?php echo t('highlow_title'); ?></h2>
    <div class="minigame-layout" data-highlow-game data-highlow-win="<?php echo t('highlow_win'); ?>" data-highlow-lose="<?php echo t('highlow_lose'); ?>" data-highlow-push="<?php echo t('highlow_push'); ?>">
        <div class="card highlow-panel">
            <div class="card-display" data-card-display data-value="7" data-suit="hearts">
                <div class="card-corner top">
                    <span class="card-value">7</span>
                    <span class="card-suit">♥</span>
                </div>
                <div class="card-center">♥</div>
                <div class="card-corner bottom">
                    <span class="card-value">7</span>
                    <span class="card-suit">♥</span>
                </div>
            </div>
            <div class="highlow-result" data-highlow-result><?php echo t('highlow_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <label><?php echo t('highlow_pick'); ?></label>
            <select class="minigame-highlow-pick">
                <option value="high"><?php echo t('high'); ?></option>
                <option value="low"><?php echo t('low'); ?></option>
            </select>
            <label>Риск</label>
            <select class="minigame-highlow-risk">
                <option value="safe">Безопасно x1.7</option>
                <option value="risk">Риск x2.4</option>
            </select>
            <div class="minigame-note">
                <strong>Высоко/Низко</strong>
                <p>Безопасный режим возвращает ставку на «7», риск — только победа или проигрыш.</p>
            </div>
            <?php if ($isDemo): ?>
                <div class="minigame-note">
                    <strong><?php echo t('demo_mode_title'); ?></strong>
                    <p><?php echo t('demo_mode_body'); ?></p>
                </div>
            <?php endif; ?>
            <button class="btn minigame-play" data-minigame="highlow" <?php echo $isDemo ? 'disabled' : ''; ?>>
                <?php echo t('play_now'); ?>
            </button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
