<?php
require __DIR__ . '/layout.php';
require_staff();
$stats = [
    'users' => db()->query('SELECT COUNT(*) AS total FROM users')->fetch()['total'] ?? 0,
    'payments' => db()->query('SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = "paid"')->fetch()['total'] ?? 0,
    'withdrawals' => db()->query('SELECT COALESCE(SUM(amount), 0) AS total FROM withdrawals WHERE status IN ("pending", "approved")')->fetch()['total'] ?? 0,
    'bets' => db()->query('SELECT COALESCE(SUM(bet), 0) AS total FROM game_logs')->fetch()['total'] ?? 0,
    'wins' => db()->query('SELECT COALESCE(SUM(win), 0) AS total FROM game_logs')->fetch()['total'] ?? 0,
];
$latestGames = db()->query('SELECT slot, bet, win, created_at FROM game_logs ORDER BY id DESC LIMIT 10')->fetchAll();
admin_header('Дашборд');
?>
<div class="section">
    <h2>Дашборд</h2>
    <div class="grid-two">
        <div class="card">Пользователи: <?php echo (int) $stats['users']; ?></div>
        <div class="card">Поступления: <?php echo number_format($stats['payments'], 2, '.', ' '); ?>₽</div>
        <div class="card">Выводы: <?php echo number_format($stats['withdrawals'], 2, '.', ' '); ?>₽</div>
        <div class="card">Ставки: <?php echo number_format($stats['bets'], 2, '.', ' '); ?>₽</div>
        <div class="card">Выигрыши: <?php echo number_format($stats['wins'], 2, '.', ' '); ?>₽</div>
    </div>
    <h3 class="section-subtitle">Последние спины</h3>
    <div class="cards">
        <?php foreach ($latestGames as $game): ?>
            <div class="card"><?php echo $game['slot']; ?> • <?php echo $game['bet']; ?>₽ → <?php echo $game['win']; ?>₽ • <?php echo $game['created_at']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
