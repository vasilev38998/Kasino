<?php
require __DIR__ . '/helpers.php';
$user = current_user();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        $message = 'Ошибка безопасности.';
    } elseif (isset($_POST['join_tournament'])) {
        $tournamentId = (int) ($_POST['tournament_id'] ?? 0);
        $entry = tournament_entry((int) $user['id'], $tournamentId);
        if ($entry) {
            $message = 'Вы уже участвуете в турнире.';
        } else {
            $stmt = db()->prepare('SELECT * FROM tournaments WHERE id = ?');
            $stmt->execute([$tournamentId]);
            $tournament = $stmt->fetch();
            if (!$tournament) {
                $message = 'Турнир не найден.';
            } else {
                $now = time();
                if (!$tournament['is_active']) {
                    $message = 'Турнир недоступен.';
                } elseif (strtotime($tournament['ends_at']) < $now) {
                    $message = 'Турнир уже завершён.';
                } else {
                    $entryFee = (float) $tournament['entry_fee'];
                    if ($entryFee > 0) {
                        $balance = user_balance((int) $user['id']);
                        if ($balance < $entryFee) {
                            $message = 'Недостаточно средств для входа.';
                        } else {
                            db()->prepare('UPDATE balances SET balance = balance - ? WHERE user_id = ?')
                                ->execute([$entryFee, $user['id']]);
                        }
                    }
                    if (!$message) {
                        db()->prepare('INSERT INTO tournament_entries (tournament_id, user_id, points, best_win, spins) VALUES (?, ?, 0, 0, 0)')
                            ->execute([$tournamentId, $user['id']]);
                        $message = 'Вы успешно вступили в турнир!';
                    }
                }
            }
        }
    } elseif (isset($_POST['claim_tournament'])) {
        $result = claim_tournament_reward((int) $user['id'], (int) ($_POST['tournament_id'] ?? 0));
        $message = $result['message'];
    }
}
$tournaments = active_tournaments();
render_header(t('promotions_title'));
?>
<section class="section">
    <h2><?php echo site_setting('promotions_title', t('promotions_title')); ?></h2>
    <?php if ($message): ?>
        <div class="card notice-card"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
    <?php endif; ?>
    <div class="cards">
        <div class="card promo-card">
            <strong><?php echo site_setting('promo_vip_title', t('promo_vip_title')); ?></strong>
            <p><?php echo site_setting('promo_vip_desc', t('promo_vip_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 55%"></div>
            </div>
            <div class="mission-meta">
                <span>55% • осталось 2 дня</span>
                <span>50 000₽</span>
            </div>
        </div>
        <div class="card promo-card">
            <strong><?php echo site_setting('promo_streak_title', t('promo_streak_title')); ?></strong>
            <p><?php echo site_setting('promo_streak_desc', t('promo_streak_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 30%"></div>
            </div>
            <div class="mission-meta">
                <span>3 / 10</span>
                <span>+15 FS</span>
            </div>
        </div>
        <div class="card promo-card">
            <strong><?php echo site_setting('promo_codes_title', t('promo_codes_title')); ?></strong>
            <p><?php echo site_setting('promo_codes_desc', t('promo_codes_desc')); ?></p>
            <div class="promo-tags">
                <?php
                $codes = array_filter(array_map('trim', explode(',', site_setting('promo_codes_list', 'LUX100, NEON50, WEEKEND'))));
                foreach ($codes as $code):
                ?>
                    <span class="tag"><?php echo htmlspecialchars($code, ENT_QUOTES); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<section class="section">
    <h2>Турниры</h2>
    <p class="muted">Участвуйте в гонках, собирайте очки и получайте призы из фонда.</p>
    <div class="cards tournaments-grid">
        <?php if (!$tournaments): ?>
            <div class="card promo-card">
                <strong>Новые турниры готовятся</strong>
                <p>Скоро здесь появятся новые гонки и призы.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($tournaments as $tournament): ?>
            <?php
            $startAt = strtotime($tournament['starts_at']);
            $endAt = strtotime($tournament['ends_at']);
            $now = time();
            $status = $now < $startAt ? 'Скоро' : ($now > $endAt ? 'Завершён' : 'Идёт');
            $timeLeft = $now < $startAt ? $startAt - $now : max(0, $endAt - $now);
            $daysLeft = (int) floor($timeLeft / 86400);
            $timeTail = gmdate('H:i:s', $timeLeft % 86400);
            $timeLabel = $daysLeft > 0 ? sprintf('%dд %s', $daysLeft, $timeTail) : $timeTail;
            $entry = $user ? tournament_entry((int) $user['id'], (int) $tournament['id']) : null;
            $leaderboard = tournament_leaderboard((int) $tournament['id'], 5);
            $userRank = $user ? tournament_rank((int) $tournament['id'], (int) $user['id']) : null;
            ?>
            <div class="card promo-card tournament-card">
                <div class="tournament-head">
                    <strong><?php echo htmlspecialchars($tournament['name'], ENT_QUOTES); ?></strong>
                    <span class="tag"><?php echo $status; ?></span>
                </div>
                <p><?php echo htmlspecialchars($tournament['description'], ENT_QUOTES); ?></p>
                <div class="tournament-meta">
                    <span>Фонд: <?php echo (float) $tournament['prize_pool']; ?>₽</span>
                    <span>Вход: <?php echo (float) $tournament['entry_fee']; ?>₽</span>
                </div>
                <div class="tournament-meta">
                    <span>Метрика: <?php echo htmlspecialchars($tournament['metric'], ENT_QUOTES); ?></span>
                    <span>До финиша: <?php echo $timeLabel; ?></span>
                </div>
                <div class="tournament-leaderboard">
                    <div class="leaderboard-title">Топ-5</div>
                    <?php if (!$leaderboard): ?>
                        <div class="muted small">Пока нет результатов</div>
                    <?php else: ?>
                        <ol>
                            <?php foreach ($leaderboard as $entryRow): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($entryRow['nickname'], ENT_QUOTES); ?></span>
                                    <span><?php echo (float) $entryRow['points']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
                <div class="mission-meta">
                    <?php if ($user && $entry): ?>
                        <span>Вы: <?php echo (float) $entry['points']; ?> • место <?php echo $userRank ?? '-'; ?></span>
                        <span>Спины: <?php echo (int) $entry['spins']; ?></span>
                    <?php else: ?>
                        <span>Войдите для участия</span>
                    <?php endif; ?>
                </div>
                <div class="mission-actions">
                    <?php if (!$user): ?>
                        <a class="btn ghost" href="/login.php">Войти</a>
                    <?php elseif ($entry): ?>
                        <?php if ($status === 'Завершён'): ?>
                            <?php if (empty($entry['reward_claimed_at'])): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="tournament_id" value="<?php echo (int) $tournament['id']; ?>">
                                    <button class="btn" type="submit" name="claim_tournament">Забрать награду</button>
                                </form>
                            <?php else: ?>
                                <span class="badge">Награда получена</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge">Вы участвуете</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="tournament_id" value="<?php echo (int) $tournament['id']; ?>">
                            <button class="btn" type="submit" name="join_tournament">Вступить</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
