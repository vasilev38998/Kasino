<?php
require __DIR__ . '/helpers.php';
require_login(true);
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
$isDemo = demo_mode_enabled() && !current_user();
render_header(t('wheel_title'));
?>
<section class="section">
    <h2><?php echo t('wheel_title'); ?></h2>
    <div class="minigame-layout" data-wheel-game data-wheel-win="<?php echo t('wheel_win'); ?>" data-wheel-lose="<?php echo t('wheel_lose'); ?>">
        <div class="card wheel-panel">
            <div class="wheel-wrap">
                <div class="wheel-pointer"></div>
                <div class="wheel" data-wheel></div>
            </div>
            <div class="wheel-result" data-wheel-result><?php echo t('wheel_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <label>Турнир</label>
            <select class="minigame-wheel-mode">
                <option value="classic">Классика</option>
                <option value="vip">VIP-режим</option>
            </select>
            <div class="minigame-note">
                <strong>Колесо арены</strong>
                <p>VIP-режим увеличивает диапазон выигрышей и поднимает волатильность.</p>
            </div>
            <p class="muted"><?php echo t('wheel_hint'); ?></p>
            <?php if ($isDemo): ?>
                <div class="minigame-note">
                    <strong><?php echo t('demo_mode_title'); ?></strong>
                    <p><?php echo t('demo_mode_body'); ?></p>
                </div>
            <?php endif; ?>
            <button class="btn minigame-play" data-minigame="wheel" <?php echo $isDemo ? 'disabled' : ''; ?>>
                <?php echo t('play_now'); ?>
            </button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
