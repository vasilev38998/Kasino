<?php
require __DIR__ . '/layout.php';
require_staff('slots');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    db()->prepare('UPDATE slots SET rtp = ?, volatility = ? WHERE slug = ?')
        ->execute([$_POST['rtp'] ?? 95.0, $_POST['volatility'] ?? 'medium', $_POST['slug'] ?? '']);
}
$slots = db()->query('SELECT slug, name, rtp, volatility FROM slots ORDER BY id')->fetchAll();
admin_header('Слоты');
?>
<div class="section">
    <h2>Слоты</h2>
    <div class="cards">
        <?php foreach ($slots as $slot): ?>
            <form class="card" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="slug" value="<?php echo $slot['slug']; ?>">
                <strong><?php echo $slot['name']; ?></strong>
                <label>RTP</label>
                <input type="number" name="rtp" step="0.1" value="<?php echo $slot['rtp']; ?>">
                <label>Волатильность</label>
                <select name="volatility">
                    <option value="low" <?php echo $slot['volatility'] === 'low' ? 'selected' : ''; ?>>low</option>
                    <option value="medium" <?php echo $slot['volatility'] === 'medium' ? 'selected' : ''; ?>>medium</option>
                    <option value="high" <?php echo $slot['volatility'] === 'high' ? 'selected' : ''; ?>>high</option>
                </select>
                <button class="btn" type="submit">Сохранить</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
