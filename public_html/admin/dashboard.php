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
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Дашборд</h2>
            <p class="muted">Сводка по активности, платежам и ставкам.</p>
        </div>
        <div class="admin-actions">
            <a class="btn" href="/admin/users.php">Перейти к пользователям</a>
            <a class="btn ghost" href="/admin/support.php">Открыть поддержку</a>
        </div>
    </div>
    <div class="admin-metrics">
        <div class="admin-metric">
            <span>Пользователи</span>
            <strong><?php echo (int) $stats['users']; ?></strong>
        </div>
        <div class="admin-metric">
            <span>Поступления</span>
            <strong><?php echo number_format($stats['payments'], 2, '.', ' '); ?>₽</strong>
        </div>
        <div class="admin-metric">
            <span>Выводы</span>
            <strong><?php echo number_format($stats['withdrawals'], 2, '.', ' '); ?>₽</strong>
        </div>
        <div class="admin-metric">
            <span>Ставки</span>
            <strong><?php echo number_format($stats['bets'], 2, '.', ' '); ?>₽</strong>
        </div>
        <div class="admin-metric">
            <span>Выигрыши</span>
            <strong><?php echo number_format($stats['wins'], 2, '.', ' '); ?>₽</strong>
        </div>
    </div>
</section>

<section class="admin-section">
    <div class="admin-section-header">
        <h3>Последние спины</h3>
        <span class="muted">Последние 10 записей</span>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>Слот</div>
            <div>Ставка</div>
            <div>Выигрыш</div>
            <div>Время</div>
        </div>
        <?php foreach ($latestGames as $game): ?>
            <div class="admin-table-row">
                <div><?php echo htmlspecialchars($game['slot'], ENT_QUOTES); ?></div>
                <div><?php echo number_format((float) $game['bet'], 2, '.', ' '); ?>₽</div>
                <div><?php echo number_format((float) $game['win'], 2, '.', ' '); ?>₽</div>
                <div class="muted"><?php echo $game['created_at']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
