<?php
require __DIR__ . '/helpers.php';
require_login(true);
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
$isDemo = demo_mode_enabled() && !current_user();
render_header(t('vault_title'));
?>
<section class="section">
    <h2><?php echo t('vault_title'); ?></h2>
    <div class="minigame-layout" data-vault-game data-vault-win="<?php echo t('vault_win'); ?>" data-vault-lose="<?php echo t('vault_lose'); ?>">
        <div class="card vault-panel">
            <div class="vault-grid">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <button class="vault-shard" type="button" data-pick="<?php echo $i; ?>">
                        <span class="vault-core"></span>
                        <span class="vault-glow"></span>
                    </button>
                <?php endfor; ?>
            </div>
            <div class="vault-result" data-vault-result><?php echo t('vault_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <label><?php echo t('vault_protocol_label'); ?></label>
            <select class="minigame-vault-mode">
                <option value="core"><?php echo t('vault_mode_core'); ?></option>
                <option value="pulse"><?php echo t('vault_mode_pulse'); ?></option>
            </select>
            <div class="minigame-note">
                <strong><?php echo t('vault_note_title'); ?></strong>
                <p><?php echo t('vault_note_body'); ?></p>
            </div>
            <?php if ($isDemo): ?>
                <div class="minigame-note">
                    <strong><?php echo t('demo_mode_title'); ?></strong>
                    <p><?php echo t('demo_mode_body'); ?></p>
                </div>
            <?php endif; ?>
            <button class="btn minigame-play" data-minigame="vault" <?php echo $isDemo ? 'disabled' : ''; ?>>
                <?php echo t('play_now'); ?>
            </button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
