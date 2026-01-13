<?php
require __DIR__ . '/helpers.php';
$slots = slots_catalog();
$game = $_GET['game'] ?? $slots[0]['slug'];
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
        <div class="card">
            <img src="<?php echo $current['icon']; ?>" alt="<?php echo $current['name']; ?>">
            <p>RTP <?php echo $current['rtp']; ?>%</p>
            <label>Ставка</label>
            <input type="number" id="bet" value="50" min="10" max="1000">
            <button class="btn" id="spin">SPIN</button>
        </div>
        <div class="card">
            <strong>Результат</strong>
            <p id="result">Ожидание спина...</p>
        </div>
    </div>
</section>
<script>
document.getElementById('spin').addEventListener('click', () => {
    fetch('/api/game.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            game: '<?php echo $current['slug']; ?>',
            bet: Number(document.getElementById('bet').value)
        })
    })
    .then(res => res.json())
    .then(data => {
        const result = document.getElementById('result');
        if (data.error) {
            result.textContent = data.error;
            return;
        }
        result.textContent = `Выигрыш: ${data.win}₽ | Комбо: ${data.combo}`;
    });
});
</script>
<?php render_footer(); ?>
