<?php
require __DIR__ . '/helpers.php';
render_header(t('leaderboard_title'));
$stmt = db()->query('SELECT nickname, total_wins FROM users ORDER BY total_wins DESC LIMIT 10');
$rows = $stmt->fetchAll();
?>
<section class="section">
    <h2><?php echo t('leaderboard_title'); ?></h2>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo htmlspecialchars($row['nickname'], ENT_QUOTES); ?> • <?php echo (int) $row['total_wins']; ?>₽</div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
