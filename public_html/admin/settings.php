<?php
require __DIR__ . '/layout.php';
require_staff('settings');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    db()->prepare('UPDATE settings SET value = ? WHERE name = ?')
        ->execute([$_POST['value'] ?? '', $_POST['name'] ?? '']);
}
$settings = db()->query('SELECT name, value FROM settings ORDER BY name')->fetchAll();
admin_header('Настройки');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Настройки</h2>
            <p class="muted">Быстрое изменение ключевых параметров сайта.</p>
        </div>
        <div class="admin-actions">
            <span class="admin-pill"><?php echo count($settings); ?> параметров</span>
        </div>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>Параметр</div>
            <div>Значение</div>
            <div>Действия</div>
        </div>
        <?php foreach ($settings as $setting): ?>
            <form class="admin-table-row" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="name" value="<?php echo $setting['name']; ?>">
                <div>
                    <strong><?php echo $setting['name']; ?></strong>
                </div>
                <div>
                    <input type="text" name="value" value="<?php echo htmlspecialchars($setting['value'], ENT_QUOTES); ?>">
                </div>
                <div>
                    <button class="btn" type="submit">Сохранить</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
