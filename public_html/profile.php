<?php
require __DIR__ . '/helpers.php';
require_login();
$user = current_user();
$balance = user_balance((int) $user['id']);
render_header(t('profile_title'));
?>
<section class="section">
    <h2><?php echo t('profile_title'); ?></h2>
    <div class="grid-two">
        <div class="card">
            <p>Email: <?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></p>
            <p><?php echo t('balance'); ?>: <?php echo number_format($balance, 2, '.', ' '); ?>₽</p>
            <p>Статус: <?php echo $user['status']; ?></p>
        </div>
        <div class="card">
            <strong>Риск-профиль</strong>
            <?php
            $stmt = db()->prepare('SELECT risk_score, flags FROM user_risk WHERE user_id = ?');
            $stmt->execute([$user['id']]);
            $risk = $stmt->fetch();
            ?>
            <p>Risk Score: <?php echo $risk ? $risk['risk_score'] : 0; ?>/100</p>
            <p>Флаги: <?php echo $risk ? $risk['flags'] : 'нет'; ?></p>
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
</section>
<?php render_footer(); ?>
