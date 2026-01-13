<?php
require __DIR__ . '/layout.php';
require_staff('cms');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    db()->prepare('UPDATE settings SET value = ? WHERE name = ?')
        ->execute([$_POST['value'] ?? '', $_POST['name'] ?? '']);
}
$pages = db()->query("SELECT name, value FROM settings WHERE name IN ('terms', 'privacy')")->fetchAll();
admin_header('CMS');
?>
<div class="section">
    <h2>Контент</h2>
    <?php foreach ($pages as $page): ?>
        <form class="form-card" method="post">
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="name" value="<?php echo $page['name']; ?>">
            <label><?php echo $page['name']; ?></label>
            <textarea name="value" rows="6" required><?php echo htmlspecialchars($page['value'], ENT_QUOTES); ?></textarea>
            <button class="btn" type="submit">Сохранить</button>
        </form>
    <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
