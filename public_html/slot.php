<?php
require __DIR__ . '/helpers.php';
$slots = slots_catalog();
$game = $_GET['game'] ?? $slots[0]['slug'];
$config = require __DIR__ . '/config.php';
$minBet = (int) $config['game']['min_bet'];
$maxBet = (int) $config['game']['max_bet'];
$current = $slots[0];
foreach ($slots as $slot) {
    if ($slot['slug'] === $game) {
        $current = $slot;
    }
}
render_header($current['name']);
?>
<section class="section slot-section theme-<?php echo $current['theme']; ?>" data-slot-theme="<?php echo $current['theme']; ?>">
    <div class="slot-header">
        <div>
            <h2><?php echo $current['name']; ?></h2>
            <p class="muted">RTP <?php echo $current['rtp']; ?>% • Volatility: High</p>
        </div>
        <img src="<?php echo $current['icon']; ?>" alt="<?php echo $current['name']; ?>" class="slot-icon">
    </div>
    <div class="slot-stage">
        <div class="slot-canvas card slot-panel" data-slot-game="<?php echo $current['slug']; ?>">
            <canvas class="slot-reels" width="720" height="420"></canvas>
            <div class="slot-overlay">
                <div class="slot-status">Готов к спину</div>
                <div class="slot-win">0₽</div>
            </div>
        </div>
        <div class="card slot-controls">
            <div class="slot-info">
                <span class="badge">Фриспины: 3+ scatter</span>
                <span class="badge">Множители: x2-x20</span>
            </div>
            <label>Ставка</label>
            <input type="number" class="slot-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <div class="slot-buttons">
                <button class="btn slot-spin" type="button">SPIN</button>
                <button class="btn ghost slot-auto" type="button" data-auto="10">AUTO x10</button>
            </div>
            <div class="slot-result">
                <strong>Результат</strong>
                <p class="slot-result-text">Ожидание спина...</p>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
