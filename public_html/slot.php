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
$payouts = $current['payouts'] ?? [];
$labels = $current['symbol_labels'] ?? [];
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
        <div class="card slot-controls">
            <div class="slot-info">
                <span class="badge"><?php echo $current['mechanic']; ?></span>
                <span class="badge">Бонус: 3+ scatter</span>
            </div>
            <button class="btn ghost slot-hints-toggle" type="button" data-slot-hints-toggle>
                Комбинации и выплаты
            </button>
            <div class="slot-hints" data-slot-hints>
                <strong>Комбо и выплаты</strong>
                <ul>
                    <?php $symbolList = implode(', ', array_values($labels)); ?>
                    <?php $winType = $current['win_type'] ?? 'count'; ?>
                    <?php foreach ($payouts as $tier): ?>
                        <?php $count = (int) ($tier['count'] ?? 0); ?>
                        <?php $symbol = $symbolList ?: $current['scatter']; ?>
                        <?php $multiplier = (float) ($tier['multiplier'] ?? 0); ?>
                        <li data-multiplier="<?php echo $multiplier; ?>">
                            <?php if ($winType === 'cluster'): ?>
                                Кластер <?php echo $count; ?>+ • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'row'): ?>
                                Ряд <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'column'): ?>
                                Колонна <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'diagonal'): ?>
                                Диагональ <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'edge'): ?>
                                Край <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'corner'): ?>
                                Углы <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'center'): ?>
                                Центр <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php elseif ($winType === 'cross'): ?>
                                Крест <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php else: ?>
                                <?php echo $count; ?>+ одинаковых • x<?php echo $multiplier; ?>
                            <?php endif; ?>
                            <span class="slot-hint-win">0₽</span>
                        </li>
                    <?php endforeach; ?>
                    <li class="slot-hint-symbols">Символы: <?php echo $symbol; ?></li>
                    <?php if (!empty($current['rare_symbols'])): ?>
                        <li class="slot-hint-rare">
                            Редкие: <?php echo implode(', ', array_map(fn($id) => $labels[$id] ?? $id, $current['rare_symbols'])); ?>
                            • бонус x<?php echo (float) ($current['rare_bonus'] ?? 0); ?>
                        </li>
                    <?php endif; ?>
                </ul>
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
        <div class="slot-field">
            <div class="slot-canvas card slot-panel" data-slot-game="<?php echo $current['slug']; ?>" data-cols="<?php echo $current['cols']; ?>" data-rows="<?php echo $current['rows']; ?>">
                <canvas class="slot-reels" width="720" height="420"></canvas>
            </div>
            <div class="slot-overlay">
                <div class="slot-status">Готов к спину</div>
                <div class="slot-win">0₽</div>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
