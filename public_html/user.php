<?php
require __DIR__ . '/helpers.php';
$userId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT id, nickname, total_wins, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if (!$profile) {
    header('Location: /leaderboard.php');
    exit;
}
$logs = db()->prepare('SELECT slot, bet, win, created_at FROM game_logs WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$logs->execute([$profile['id']]);
render_header('Профиль игрока');
?>
<section class="section">
    <h2>Профиль игрока</h2>
    <div class="grid-two">
        <div class="card">
            <p>Никнейм: <?php echo htmlspecialchars($profile['nickname'], ENT_QUOTES); ?></p>
            <p>Всего выигрышей: <?php echo number_format($profile['total_wins'], 2, '.', ' '); ?>₽</p>
            <p>Дата регистрации: <?php echo $profile['created_at']; ?></p>
        </div>
        <div class="card">
            <strong>Последние спины</strong>
            <?php foreach ($logs->fetchAll() as $log): ?>
                <p><?php echo htmlspecialchars($log['slot'], ENT_QUOTES); ?> • <?php echo $log['bet']; ?>₽ → <?php echo $log['win']; ?>₽</p>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php render_footer(); ?>
