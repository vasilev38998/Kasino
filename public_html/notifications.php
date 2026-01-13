<?php
require __DIR__ . '/helpers.php';
require_login();
$user = current_user();
$stmt = db()->prepare('SELECT title, message, type, created_at FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 50');
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();
render_header(t('notifications_title'));
?>
<section class="section">
    <h2><?php echo t('notifications_title'); ?></h2>
    <div class="cards">
        <?php foreach ($items as $item): ?>
            <div class="card">
                <strong><?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?></strong>
                <p><?php echo htmlspecialchars($item['message'], ENT_QUOTES); ?></p>
                <span class="badge"><?php echo $item['type']; ?> • <?php echo $item['created_at']; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
