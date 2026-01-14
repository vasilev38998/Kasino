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
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Слоты</h2>
            <p class="muted">Управляйте RTP и волатильностью для каждой игры.</p>
        </div>
        <div class="admin-actions">
            <span class="admin-pill"><?php echo count($slots); ?> игр</span>
        </div>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>Слот</div>
            <div>RTP</div>
            <div>Волатильность</div>
            <div>Действия</div>
        </div>
        <?php foreach ($slots as $slot): ?>
            <form class="admin-table-row" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="slug" value="<?php echo $slot['slug']; ?>">
                <div>
                    <strong><?php echo htmlspecialchars($slot['name'], ENT_QUOTES); ?></strong>
                    <div class="muted small"><?php echo $slot['slug']; ?></div>
                </div>
                <div>
                    <input type="number" name="rtp" step="0.1" value="<?php echo $slot['rtp']; ?>">
                </div>
                <div>
                    <select name="volatility">
                        <option value="low" <?php echo $slot['volatility'] === 'low' ? 'selected' : ''; ?>>low</option>
                        <option value="medium" <?php echo $slot['volatility'] === 'medium' ? 'selected' : ''; ?>>medium</option>
                        <option value="high" <?php echo $slot['volatility'] === 'high' ? 'selected' : ''; ?>>high</option>
                    </select>
                </div>
                <div>
                    <button class="btn" type="submit">Сохранить</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
