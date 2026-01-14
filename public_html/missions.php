<?php
require __DIR__ . '/helpers.php';
$user = current_user();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } else {
        $result = claim_mission_reward((int) $user['id'], (int) ($_POST['mission_id'] ?? 0));
        $message = $result['message'];
    }
}
$missions = active_missions();
$missionIds = array_map(fn($mission) => (int) $mission['id'], $missions);
$progressMap = $user ? mission_progress_map((int) $user['id'], $missionIds) : [];
render_header(t('missions_title'));
?>
<section class="section">
    <h2><?php echo site_setting('missions_title', t('missions_title')); ?></h2>
    <p class="muted"><?php echo site_setting('missions_subtitle', t('missions_subtitle')); ?></p>
    <?php if ($message): ?>
        <div class="card notice-card"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
    <?php endif; ?>
    <div class="cards missions-grid">
        <?php if (!$missions): ?>
            <div class="card mission-card">
                <strong>Миссии скоро появятся</strong>
                <p>Администратор готовит новые задания. Проверьте позже.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($missions as $mission): ?>
            <?php
            $progressRow = normalize_mission_progress($mission, $progressMap[$mission['id']] ?? null);
            $progressValue = (float) ($progressRow['progress'] ?? 0);
            $targetValue = (float) $mission['target_value'];
            $percent = $targetValue > 0 ? min(100, ($progressValue / $targetValue) * 100) : 0;
            $completed = $progressValue >= $targetValue && $targetValue > 0;
            $claimed = !empty($progressRow['claimed_at']);
            $periodLabel = match ($mission['period']) {
                'daily' => 'Ежедневная',
                'weekly' => 'Еженедельная',
                default => 'Постоянная',
            };
            ?>
            <div class="card mission-card">
                <div class="mission-head">
                    <strong><?php echo htmlspecialchars($mission['name'], ENT_QUOTES); ?></strong>
                    <span class="tag"><?php echo $periodLabel; ?></span>
                </div>
                <p><?php echo htmlspecialchars($mission['description'], ENT_QUOTES); ?></p>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                </div>
                <div class="mission-meta">
                    <span><?php echo min($progressValue, $targetValue); ?> / <?php echo $targetValue; ?></span>
                    <span>+<?php echo (float) $mission['reward_amount']; ?>₽</span>
                </div>
                <div class="mission-actions">
                    <?php if (!$user): ?>
                        <span class="muted small">Войдите, чтобы отслеживать прогресс</span>
                    <?php elseif ($completed && !$claimed): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="mission_id" value="<?php echo (int) $mission['id']; ?>">
                            <button class="btn" type="submit">Получить награду</button>
                        </form>
                    <?php elseif ($claimed): ?>
                        <span class="badge">Награда получена</span>
                    <?php else: ?>
                        <span class="muted small">Прогресс в работе</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<section class="section">
    <h2><?php echo site_setting('referral_title', t('referral_title')); ?></h2>
    <div class="card referral-card">
        <div>
            <strong><?php echo site_setting('referral_reward', t('referral_reward')); ?></strong>
            <p class="muted"><?php echo site_setting('referral_desc', t('referral_desc')); ?></p>
        </div>
        <div class="referral-code">
            <span>VIP-7KX9</span>
            <button class="btn ghost" type="button"><?php echo t('referral_copy'); ?></button>
        </div>
    </div>
</section>
<?php render_footer(); ?>
