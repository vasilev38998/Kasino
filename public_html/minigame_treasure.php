<?php
require __DIR__ . '/helpers.php';
require_login();
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
render_header(t('treasure_title'));
?>
<section class="section">
    <h2><?php echo t('treasure_title'); ?></h2>
    <div class="minigame-layout" data-treasure-game data-treasure-win="<?php echo t('treasure_win'); ?>" data-treasure-lose="<?php echo t('treasure_lose'); ?>">
        <div class="card treasure-panel">
            <div class="treasure-grid">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <button class="treasure-chest" type="button" data-pick="<?php echo $i; ?>">
                        <span class="treasure-lid"></span>
                        <span class="treasure-body"></span>
                        <span class="treasure-gem"></span>
                    </button>
                <?php endfor; ?>
            </div>
            <div class="treasure-result" data-treasure-result><?php echo t('treasure_ready'); ?></div>
        </div>
        <div class="card minigame-controls">
            <label><?php echo t('bet_amount'); ?></label>
            <input type="number" name="bet" class="minigame-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <label>Режим охоты</label>
            <select class="minigame-treasure-mode">
                <option value="map">Карта сокровищ</option>
                <option value="relic">Древний артефакт</option>
            </select>
            <div class="minigame-note">
                <strong>Поиск реликвий</strong>
                <p>Карта сокровищ даёт стабильные награды, артефакт — редкие большие выигрыши.</p>
            </div>
            <p class="muted"><?php echo t('treasure_hint'); ?></p>
            <button class="btn minigame-play" data-minigame="treasure"><?php echo t('play_now'); ?></button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
