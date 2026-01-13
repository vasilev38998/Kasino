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
<section class="section">
    <h2><?php echo $current['name']; ?></h2>
    <div class="grid-two">
        <div class="card slot-panel" data-slot-game="<?php echo $current['slug']; ?>">
            <img src="<?php echo $current['icon']; ?>" alt="<?php echo $current['name']; ?>">
            <p>RTP <?php echo $current['rtp']; ?>%</p>
            <label>Ставка</label>
            <input type="number" class="slot-bet" value="<?php echo $minBet; ?>" min="<?php echo $minBet; ?>" max="<?php echo $maxBet; ?>">
            <button class="btn slot-spin" type="button">SPIN</button>
        </div>
        <div class="card">
            <strong>Результат</strong>
            <p class="slot-result">Ожидание спина...</p>
        </div>
    </div>
</section>
<?php render_footer(); ?>
