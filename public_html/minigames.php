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
        <div class="card minigame-card">
            <div class="minigame-icon vault"></div>
            <h3><?php echo t('vault_title'); ?></h3>
            <p><?php echo t('vault_subtitle'); ?></p>
            <a class="btn" href="/minigame_vault.php"><?php echo t('play_now'); ?></a>
        </div>
    </div>
</section>
<section class="section">
    <h2>Аркадные мини-игры</h2>
    <p class="muted">Быстрые игры без ставок, добавленные из открытых исходников.</p>
    <div class="cards minigames-grid">
        <div class="card minigame-card">
            <div class="minigame-icon arcade-coin"></div>
            <h3>Coin Flip Challenge</h3>
            <p>Два игрока, быстрая дуэль на орле и решке.</p>
            <a class="btn" href="/vendor/fun-games-hub/coin-flip/index.html">Играть</a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon arcade-memory"></div>
            <h3>Memory Match</h3>
            <p>Найдите все пары как можно быстрее.</p>
            <a class="btn" href="/vendor/fun-games-hub/memory-game/index.html">Играть</a>
        </div>
        <div class="card minigame-card">
            <div class="minigame-icon arcade-rps"></div>
            <h3>Rock Paper Scissors</h3>
            <p>Классическая игра на реакцию и удачу.</p>
            <a class="btn" href="/vendor/fun-games-hub/rock-paper-scissors/index.html">Играть</a>
        </div>
    </div>
</section>
<?php render_footer(); ?>
