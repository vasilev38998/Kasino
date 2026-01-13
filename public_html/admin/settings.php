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
<div class="section">
    <h2>Настройки</h2>
    <div class="cards">
        <?php foreach ($settings as $setting): ?>
            <form class="card" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="name" value="<?php echo $setting['name']; ?>">
                <label><?php echo $setting['name']; ?></label>
                <input type="text" name="value" value="<?php echo htmlspecialchars($setting['value'], ENT_QUOTES); ?>">
                <button class="btn" type="submit">Сохранить</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
