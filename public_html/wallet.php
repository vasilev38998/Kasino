<?php
require __DIR__ . '/helpers.php';
require_login();
$user = current_user();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } else {
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $action = $_POST['action'] ?? '';
        $balance = user_balance((int) $user['id']);
        if ($action === 'deposit') {
            db()->prepare('INSERT INTO payments (user_id, amount, status, provider) VALUES (?, ?, "paid", "sbp")')
                ->execute([$user['id'], $amount]);
            db()->prepare('INSERT INTO balances (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
                ->execute([$user['id'], $amount]);
            $message = 'Баланс пополнен.';
        }
        if ($action === 'withdraw') {
            $config = require __DIR__ . '/config.php';
            $limit = $config['security']['rate_limit']['withdrawal'];
            if (rate_limited('withdrawal', $limit['window'], $limit['max'])) {
                $message = 'Слишком много заявок.';
            } elseif ($balance < $amount) {
                $message = t('insufficient_funds');
            } else {
                db()->prepare('INSERT INTO withdrawals (user_id, amount, status, details) VALUES (?, ?, "pending", ?)')
                    ->execute([$user['id'], $amount, $_POST['details'] ?? '']);
                db()->prepare('UPDATE balances SET balance = balance - ? WHERE user_id = ?')
                    ->execute([$amount, $user['id']]);
                $message = 'Заявка на вывод отправлена.';
            }
        }
    }
}
$balance = user_balance((int) $user['id']);
render_header(t('wallet_title'));
?>
<section class="section">
    <h2><?php echo t('wallet_title'); ?></h2>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <div class="grid-two">
        <div class="card">
            <h3><?php echo t('deposit'); ?></h3>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="deposit">
                <label>Сумма</label>
                <input type="number" name="amount" min="100" max="100000" required>
                <button class="btn" type="submit">Пополнить</button>
            </form>
        </div>
        <div class="card">
            <h3><?php echo t('withdraw'); ?></h3>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="withdraw">
                <label>Сумма</label>
                <input type="number" name="amount" min="100" required>
                <label>Реквизиты</label>
                <textarea name="details" rows="3" required></textarea>
                <button class="btn" type="submit">Отправить</button>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top:20px;">
        <strong><?php echo t('balance'); ?>:</strong> <?php echo number_format($balance, 2, '.', ' '); ?>₽
    </div>
</section>
<?php render_footer(); ?>
