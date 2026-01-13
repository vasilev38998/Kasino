<?php
require __DIR__ . '/helpers.php';
require_login();
$user = current_user();
$config = require __DIR__ . '/config.php';
$minDeposit = (int) $config['payments']['sbp']['min_amount'];
$maxDeposit = (int) $config['payments']['sbp']['max_amount'];
$walletMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $walletMessage = 'Ошибка безопасности.';
    } else {
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $action = $_POST['action'] ?? '';
        $balance = user_balance((int) $user['id']);
        if ($action === 'deposit') {
            if ($amount < $minDeposit || $amount > $maxDeposit) {
                $walletMessage = 'Сумма вне лимитов.';
            } else {
                db()->prepare('INSERT INTO payments (user_id, amount, status, provider) VALUES (?, ?, "paid", "sbp")')
                    ->execute([$user['id'], $amount]);
                db()->prepare('INSERT INTO balances (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
                    ->execute([$user['id'], $amount]);
                $walletMessage = 'Баланс пополнен.';
            }
        }
        if ($action === 'withdraw') {
            $limit = $config['security']['rate_limit']['withdrawal'];
            if (rate_limited('withdrawal', $limit['window'], $limit['max'])) {
                $walletMessage = 'Слишком много заявок.';
            } elseif ($amount <= 0) {
                $walletMessage = 'Сумма некорректна.';
            } elseif ($balance < $amount) {
                $walletMessage = t('insufficient_funds');
            } else {
                db()->prepare('INSERT INTO withdrawals (user_id, amount, status, details) VALUES (?, ?, "pending", ?)')
                    ->execute([$user['id'], $amount, $_POST['details'] ?? '']);
                db()->prepare('UPDATE balances SET balance = balance - ? WHERE user_id = ?')
                    ->execute([$amount, $user['id']]);
                $walletMessage = 'Заявка на вывод отправлена.';
            }
        }
        if ($action === 'promo') {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            if ($code === '') {
                $walletMessage = 'Введите промокод.';
            } else {
                $stmt = db()->prepare('SELECT * FROM promo_codes WHERE code = ?');
                $stmt->execute([$code]);
                $promo = $stmt->fetch();
                if (!$promo) {
                    $walletMessage = 'Промокод не найден.';
                } elseif ((int) $promo['used'] >= (int) $promo['max_uses']) {
                    $walletMessage = 'Лимит промокода исчерпан.';
                } else {
                    db()->prepare('UPDATE promo_codes SET used = used + 1 WHERE id = ?')
                        ->execute([$promo['id']]);
                    db()->prepare('INSERT INTO bonuses (user_id, type, amount, claimed_at) VALUES (?, "promo", ?, NOW())')
                        ->execute([$user['id'], $promo['amount']]);
                    db()->prepare('INSERT INTO balances (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
                        ->execute([$user['id'], $promo['amount']]);
                    $walletMessage = 'Промокод активирован.';
                }
            }
        }
    }
}
$balance = user_balance((int) $user['id']);
$payments = db()->prepare('SELECT amount, status, created_at FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$payments->execute([$user['id']]);
$withdrawals = db()->prepare('SELECT amount, status, created_at FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$withdrawals->execute([$user['id']]);
$bonuses = db()->prepare('SELECT type, amount, claimed_at FROM bonuses WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$bonuses->execute([$user['id']]);
$notifications = db()->prepare('SELECT title, message, type, created_at FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 50');
$notifications->execute([$user['id']]);
$notificationItems = $notifications->fetchAll();
render_header(t('profile_title'));
?>
<section class="section profile-section">
    <h2><?php echo t('profile_title'); ?></h2>
    <div class="card profile-hero">
        <div class="profile-hero-main">
            <div class="profile-avatar"><?php echo strtoupper(mb_substr($user['nickname'], 0, 2)); ?></div>
            <div>
                <strong class="profile-name"><?php echo htmlspecialchars($user['nickname'], ENT_QUOTES); ?></strong>
                <p class="muted"><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></p>
            </div>
        </div>
        <div class="profile-hero-stats">
            <div>
                <span class="muted">Баланс</span>
                <strong><?php echo number_format($balance, 2, '.', ' '); ?>₽</strong>
            </div>
            <div>
                <span class="muted">Статус</span>
                <strong><?php echo $user['status']; ?></strong>
            </div>
            <div>
                <span class="muted">Дата рождения</span>
                <strong><?php echo htmlspecialchars($user['birth_date'], ENT_QUOTES); ?></strong>
            </div>
        </div>
    </div>

    <div class="profile-tabs">
        <a class="profile-tab" data-profile-tab="profile" href="#profile">Профиль</a>
        <a class="profile-tab" data-profile-tab="wallet" href="#wallet"><?php echo t('wallet_title'); ?></a>
        <a class="profile-tab" data-profile-tab="notifications" href="#notifications"><?php echo t('notifications_title'); ?></a>
    </div>

    <div class="profile-panels">
        <div class="profile-panel" data-profile-panel="profile" id="profile">
            <div class="grid-two">
                <div class="card profile-card">
                    <div class="profile-card-header">
                        <strong>Данные аккаунта</strong>
                        <span class="badge">VIP</span>
                    </div>
                    <p>Email: <?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></p>
                    <p>Никнейм: <?php echo htmlspecialchars($user['nickname'], ENT_QUOTES); ?></p>
                    <p>Дата рождения: <?php echo htmlspecialchars($user['birth_date'], ENT_QUOTES); ?></p>
                    <p><?php echo t('balance'); ?>: <?php echo number_format($balance, 2, '.', ' '); ?>₽</p>
                    <p>Статус: <?php echo $user['status']; ?></p>
                </div>
                <div class="card profile-card">
                    <strong>Риск-профиль</strong>
                    <?php
                    $stmt = db()->prepare('SELECT risk_score, flags FROM user_risk WHERE user_id = ?');
                    $stmt->execute([$user['id']]);
                    $risk = $stmt->fetch();
                    ?>
                    <div class="profile-risk">
                        <div>
                            <span class="muted">Risk Score</span>
                            <strong><?php echo $risk ? $risk['risk_score'] : 0; ?>/100</strong>
                        </div>
                        <div>
                            <span class="muted">Флаги</span>
                            <strong><?php echo $risk ? $risk['flags'] : 'нет'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid-two profile-social">
                <div class="card">
                    <strong>Привязка VK</strong>
                    <form class="social-bind" data-provider="vk">
                        <input type="text" name="provider_id" placeholder="VK ID" required>
                        <button class="btn" type="submit">Привязать</button>
                    </form>
                </div>
                <div class="card">
                    <strong>Привязка Telegram</strong>
                    <form class="social-bind" data-provider="telegram">
                        <input type="text" name="provider_id" placeholder="Telegram ID" required>
                        <button class="btn" type="submit">Привязать</button>
                    </form>
                </div>
            </div>
            <div class="section-subtitle">Последние спины</div>
            <div class="cards">
                <?php
                $logs = db()->prepare('SELECT slot, bet, win, created_at FROM game_logs WHERE user_id = ? ORDER BY id DESC LIMIT 6');
                $logs->execute([$user['id']]);
                foreach ($logs->fetchAll() as $log): ?>
                    <div class="card"><?php echo htmlspecialchars($log['slot'], ENT_QUOTES); ?> • <?php echo $log['bet']; ?>₽ → <?php echo $log['win']; ?>₽ • <?php echo $log['created_at']; ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="profile-panel" data-profile-panel="wallet" id="wallet">
            <?php if ($walletMessage): ?>
                <p class="profile-alert"><?php echo $walletMessage; ?></p>
            <?php endif; ?>
            <div class="grid-two">
                <div class="card">
                    <h3><?php echo t('deposit'); ?></h3>
                    <form method="post" action="/profile.php#wallet">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="deposit">
                        <label>Сумма</label>
                        <input type="number" name="amount" min="<?php echo $minDeposit; ?>" max="<?php echo $maxDeposit; ?>" required>
                        <button class="btn" type="submit">Пополнить</button>
                    </form>
                </div>
                <div class="card">
                    <h3><?php echo t('withdraw'); ?></h3>
                    <form method="post" action="/profile.php#wallet">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="withdraw">
                        <label>Сумма</label>
                        <input type="number" name="amount" min="100" required>
                        <label>Реквизиты</label>
                        <p class="muted small">Можно указать номер банковской карты или номер телефона + банк СБП.</p>
                        <textarea name="details" rows="3" class="no-resize" required></textarea>
                        <button class="btn" type="submit">Отправить</button>
                    </form>
                </div>
                <div class="card">
                    <h3><?php echo t('promo_title'); ?></h3>
                    <form method="post" action="/profile.php#wallet">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="promo">
                        <label><?php echo t('promo_code'); ?></label>
                        <input type="text" name="code" required>
                        <button class="btn" type="submit"><?php echo t('promo_apply'); ?></button>
                    </form>
                </div>
            </div>
            <div class="card card-spaced">
                <strong><?php echo t('balance'); ?>:</strong> <?php echo number_format($balance, 2, '.', ' '); ?>₽
            </div>
            <div class="section-subtitle"><?php echo t('wallet_history'); ?></div>
            <div class="grid-two">
                <div class="card">
                    <strong><?php echo t('history_deposits'); ?></strong>
                    <?php foreach ($payments->fetchAll() as $row): ?>
                        <p><?php echo $row['created_at']; ?> • <?php echo $row['amount']; ?>₽ • <?php echo $row['status']; ?></p>
                    <?php endforeach; ?>
                </div>
                <div class="card">
                    <strong><?php echo t('history_withdrawals'); ?></strong>
                    <?php foreach ($withdrawals->fetchAll() as $row): ?>
                        <p><?php echo $row['created_at']; ?> • <?php echo $row['amount']; ?>₽ • <?php echo $row['status']; ?></p>
                    <?php endforeach; ?>
                </div>
                <div class="card">
                    <strong><?php echo t('history_bonuses'); ?></strong>
                    <?php foreach ($bonuses->fetchAll() as $row): ?>
                        <p><?php echo $row['claimed_at']; ?> • <?php echo $row['amount']; ?>₽ • <?php echo $row['type']; ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="profile-panel" data-profile-panel="notifications" id="notifications">
            <div class="cards">
                <?php foreach ($notificationItems as $item): ?>
                    <div class="card">
                        <strong><?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?></strong>
                        <p><?php echo htmlspecialchars($item['message'], ENT_QUOTES); ?></p>
                        <span class="badge"><?php echo $item['type']; ?> • <?php echo $item['created_at']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
