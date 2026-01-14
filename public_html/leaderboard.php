<?php
require __DIR__ . '/helpers.php';
$period = $_GET['period'] ?? 'all';
$allowed = ['day', 'week', 'all'];
if (!in_array($period, $allowed, true)) {
    $period = 'all';
}
if ($period === 'day') {
    $stmt = db()->prepare('SELECT u.id, u.nickname, COALESCE(SUM(g.win), 0) AS total_wins FROM game_logs g JOIN users u ON u.id = g.user_id WHERE g.created_at >= CURDATE() GROUP BY u.id, u.nickname ORDER BY total_wins DESC LIMIT 10');
    $stmt->execute();
    $rows = $stmt->fetchAll();
} elseif ($period === 'week') {
    $stmt = db()->prepare('SELECT u.id, u.nickname, COALESCE(SUM(g.win), 0) AS total_wins FROM game_logs g JOIN users u ON u.id = g.user_id WHERE g.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY u.id, u.nickname ORDER BY total_wins DESC LIMIT 10');
    $stmt->execute();
    $rows = $stmt->fetchAll();
} else {
    $stmt = db()->query('SELECT id, nickname, total_wins FROM users ORDER BY total_wins DESC LIMIT 10');
    $rows = $stmt->fetchAll();
}
render_header(t('leaderboard_title'));
?>
<section class="section">
    <h2><?php echo t('leaderboard_title'); ?></h2>
    <div class="tabs">
        <a class="tab <?php echo $period === 'day' ? 'active' : ''; ?>" href="/leaderboard.php?period=day">День</a>
        <a class="tab <?php echo $period === 'week' ? 'active' : ''; ?>" href="/leaderboard.php?period=week">Неделя</a>
        <a class="tab <?php echo $period === 'all' ? 'active' : ''; ?>" href="/leaderboard.php?period=all">Все время</a>
    </div>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card">
                <a href="/user.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nickname'], ENT_QUOTES); ?></a>
                • <?php echo number_format($row['total_wins'], 2, '.', ' '); ?>₽
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
