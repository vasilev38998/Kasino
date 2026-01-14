<?php
require __DIR__ . '/helpers.php';
$stmt = db()->prepare('SELECT value FROM settings WHERE name = ?');
$stmt->execute(['terms']);
$content = $stmt->fetchColumn() ?: '';
render_header(t('terms_title'));
?>
<section class="section">
    <h2><?php echo t('terms_title'); ?></h2>
    <div class="card">
        <p><?php echo htmlspecialchars($content, ENT_QUOTES); ?></p>
    </div>
</section>
<?php render_footer(); ?>
