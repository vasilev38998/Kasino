<?php
require __DIR__ . '/helpers.php';
require_login();
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
render_header(t('dice_title'));
?>
<section class="section">
    <h2><?php echo t('dice_title'); ?></h2>
    <div class="minigame-layout" data-dice-game data-dice-win="<?php echo t('dice_win'); ?>" data-dice-lose="<?php echo t('dice_lose'); ?>">
        <div class="card dice-panel">
            <div class="dice-display" data-dice-display data-value="1">
                <?php for ($i = 0; $i < 9; $i++): ?>
                    <span class="pip"></span>
                <?php endfor; ?>
            </div>
            <div class="dice-result" data-dice-result><?php echo t('dice_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <label>Режим</label>
            <select class="minigame-dice-mode">
                <option value="exact">Точная грань x6</option>
                <option value="dual">Две грани x3</option>
            </select>
            <label><?php echo t('dice_pick'); ?></label>
            <select class="minigame-dice-pick">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <label class="minigame-dice-pick-second-label">Вторая грань</label>
            <select class="minigame-dice-pick-second">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <div class="minigame-note">
                <strong>Кости мастера</strong>
                <p>Точная грань даёт максимум, а двойной выбор снижает риск.</p>
            </div>
            <button class="btn minigame-play" data-minigame="dice"><?php echo t('play_now'); ?></button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
