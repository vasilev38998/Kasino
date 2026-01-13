<?php
require __DIR__ . '/helpers.php';
require_login();
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
render_header(t('plinko_title'));
?>
<section class="section">
    <div class="plinko-header">
        <div>
            <span class="plinko-badge">Plinko</span>
            <h2><?php echo t('plinko_title'); ?></h2>
        </div>
        <div class="plinko-status">
            <span class="badge">Баланс: <strong data-balance>0₽</strong></span>
            <span class="badge">Сессия: online</span>
        </div>
    </div>
    <div class="plinko-shell">
        <div class="card plinko-sidebar">
            <div class="plinko-tabs">
                <button class="plinko-tab is-active" type="button">Ручные ставки</button>
                <button class="plinko-tab" type="button">Авто ставки</button>
            </div>

            <div class="plinko-block">
                <label><?php echo t('bet_amount'); ?></label>
                <div class="plinko-bet-row">
                    <input type="number" name="bet" class="minigame-bet plinko-bet-input" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
                    <div class="plinko-bet-actions">
                        <button class="btn ghost" type="button">1/2</button>
                        <button class="btn ghost" type="button">x2</button>
                    </div>
                </div>
                <div class="plinko-quick">
                    <button class="btn ghost" type="button">+1₽</button>
                    <button class="btn ghost" type="button">+10₽</button>
                    <button class="btn ghost" type="button">+100₽</button>
                    <button class="btn ghost" type="button">ALL</button>
                </div>
            </div>

            <div class="plinko-block">
                <label>Кол-во ставок</label>
                <input type="number" class="plinko-bet-input" value="10" min="1">
            </div>

            <div class="plinko-block">
                <label><?php echo t('plinko_difficulty'); ?></label>
                <div class="plinko-difficulty plinko-risk" role="radiogroup" aria-label="<?php echo t('plinko_difficulty'); ?>">
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
            </div>

            <div class="plinko-block">
                <label>Количество пинов</label>
                <div class="plinko-pins">
                    <?php foreach ([8, 9, 10, 11, 12, 13, 14, 15, 16] as $pins): ?>
                        <button class="btn ghost<?php echo $pins === 16 ? ' is-active' : ''; ?>" type="button"><?php echo $pins; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="btn plinko-play minigame-play" data-minigame="plinko"><?php echo t('play_now'); ?></button>
            <p class="muted plinko-hint"><?php echo t('plinko_hint'); ?></p>
        </div>

        <div class="card plinko-board" data-plinko-game data-plinko-label="<?php echo t('plinko_result'); ?>">
            <div class="plinko-panel">
                <canvas class="plinko-canvas" width="520" height="520"></canvas>
                <div class="plinko-slots" data-plinko-slots></div>
                <div class="plinko-result" data-plinko-result><?php echo t('plinko_ready'); ?></div>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
