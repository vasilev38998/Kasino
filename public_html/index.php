<?php
require __DIR__ . '/helpers.php';
render_header('Kasino Lux');
$user = current_user();
$slots = slots_catalog();
$config = require __DIR__ . '/config.php';
$heroSlots = array_slice($slots, 0, 4);
?>
<section class="neo-hero">
    <div class="neo-hero-bg"></div>
    <div class="neo-hero-content">
        <div class="neo-hero-copy">
            <div class="neo-hero-badge">Kasino Lux • Neon Lab</div>
            <h1>Собирай ритм выигрышей и открывай новые уровни удачи.</h1>
            <p>Новые кейсы, слоты и миссии в одном визуальном пространстве: от мягких бонусов до редких вспышек джекпота.</p>
            <div class="neo-hero-actions">
                <?php if ($user): ?>
                    <a class="btn" href="/cases.php">Открыть кейсы</a>
                    <a class="btn ghost" href="/slots.php"><?php echo t('cta_play'); ?></a>
                <?php else: ?>
                    <a class="btn" href="/register.php"><?php echo t('cta_register'); ?></a>
                    <a class="btn ghost" href="/login.php"><?php echo t('nav_login'); ?></a>
                <?php endif; ?>
                <button class="btn ghost" data-install hidden>Установить PWA</button>
            </div>
            <div class="neo-hero-metrics">
                <div class="neo-metric">
                    <span>Пиковый RTP</span>
                    <strong>97.1%</strong>
                </div>
                <div class="neo-metric">
                    <span>Активные игроки</span>
                    <strong>21 680</strong>
                </div>
                <div class="neo-metric">
                    <span>Бонус дня</span>
                    <strong>+500₽</strong>
                </div>
            </div>
        </div>
        <div class="neo-hero-panel">
            <div class="neo-panel-glow"></div>
            <?php if ($user): ?>
                <div class="neo-panel-row">
                    <span><?php echo t('balance'); ?></span>
                    <strong data-balance>0₽</strong>
                </div>
                <div class="neo-panel-row">
                    <span>Лига</span>
                    <strong>Pulse</strong>
                </div>
            <?php else: ?>
                <div class="neo-panel-row">
                    <span>Статус</span>
                    <strong>Гость</strong>
                </div>
                <div class="neo-panel-row">
                    <span>Прогресс</span>
                    <strong>Начните с бонуса</strong>
                </div>
            <?php endif; ?>
            <div class="neo-panel-slots">
                <?php foreach ($heroSlots as $slot): ?>
                    <div class="neo-panel-slot">
                        <img src="<?php echo $slot['icon']; ?>" alt="<?php echo $slot['name']; ?>">
                        <div>
                            <strong><?php echo $slot['name']; ?></strong>
                            <span>RTP <?php echo $slot['rtp']; ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="neo-panel-cta">
                <a class="btn ghost" href="/cases.php">Смотреть кейсы</a>
            </div>
        </div>
    </div>
</section>

<section class="section neo-section">
    <div class="section-header">
        <div>
            <h2>Кейсы сезона</h2>
            <p class="muted">Редкие комбинации, которые дают шанс уйти в плюс.</p>
        </div>
        <a class="btn ghost" href="/cases.php">Все кейсы</a>
    </div>
    <div class="cards cases-grid">
        <div class="card case-card" style="--case-accent: #ff6b6b;">
            <div class="case-card-header">
                <div class="case-badge">Стоимость: 99₽</div>
                <div class="case-orb"></div>
            </div>
            <h3>Crimson Spark</h3>
            <p class="muted">Быстрые призы для прогрева удачи.</p>
        </div>
        <div class="card case-card" style="--case-accent: #6c63ff;">
            <div class="case-card-header">
                <div class="case-badge">Стоимость: 249₽</div>
                <div class="case-orb"></div>
            </div>
            <h3>Neon Drift</h3>
            <p class="muted">Сбалансированный кейс с редким бустом.</p>
        </div>
        <div class="card case-card" style="--case-accent: #00d4ff;">
            <div class="case-card-header">
                <div class="case-badge">Стоимость: 499₽</div>
                <div class="case-orb"></div>
            </div>
            <h3>Pulse Vault</h3>
            <p class="muted">Редкие победы, но мощный потенциал.</p>
        </div>
    </div>
</section>

<section class="section neo-section">
    <div class="section-header">
        <div>
            <h2>Слоты и миссии в одном потоке</h2>
            <p class="muted">Выберите стиль игры — от быстрых рывков до марафона.</p>
        </div>
        <a class="btn ghost" href="/slots.php"><?php echo t('section_top'); ?></a>
    </div>
    <div class="neo-grid">
        <div class="card neo-card">
            <h3>Лайв-лента</h3>
            <p class="muted">Держите руку на пульсе: свежие победы игроков.</p>
            <div class="neo-feed" data-live-feed>
                <div>Nova • Aurora Cascade • 1 400₽</div>
                <div>Raven • Cosmic Cluster • 980₽</div>
                <div>Luna • Dragon Sticky Wilds • 2 100₽</div>
            </div>
        </div>
        <div class="card neo-card">
            <h3>Топ-слоты</h3>
            <p class="muted">Отобраны по частоте побед в этом сезоне.</p>
            <div class="neo-slot-list">
                <?php foreach ($heroSlots as $slot): ?>
                    <div class="neo-slot-row">
                        <img src="<?php echo $slot['icon']; ?>" alt="<?php echo $slot['name']; ?>">
                        <div>
                            <strong><?php echo $slot['name']; ?></strong>
                            <span>RTP <?php echo $slot['rtp']; ?>%</span>
                        </div>
                        <a class="btn ghost" href="/slot.php?game=<?php echo $slot['slug']; ?>"><?php echo t('slot_play'); ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card neo-card">
            <h3>Бонусный ритм</h3>
            <p class="muted">Три бонусные механики — каждый день.</p>
            <div class="neo-bonus-list">
                <div>
                    <strong><?php echo t('bonus_daily'); ?></strong>
                    <p><?php echo site_setting('bonus_daily_text', 'Забирайте награду каждые 24 часа.'); ?></p>
                </div>
                <div>
                    <strong><?php echo t('bonus_welcome'); ?></strong>
                    <p><?php echo site_setting('bonus_welcome_text', 'До 500₽ + вейджер 20x.'); ?></p>
                </div>
                <div>
                    <strong><?php echo t('bonus_cashback'); ?></strong>
                    <p><?php echo site_setting('bonus_cashback_text', 'Возврат до 5% каждую неделю.'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section neo-section">
    <div class="section-header">
        <div>
            <h2>Линия прогресса</h2>
            <p class="muted">Миссии, которые подстраиваются под стиль игры.</p>
        </div>
        <a class="btn ghost" href="/missions.php"><?php echo t('missions_title'); ?></a>
    </div>
    <div class="cards missions-grid">
        <div class="card mission-card">
            <strong><?php echo site_setting('mission_daily_spin', t('mission_daily_spin')); ?></strong>
            <p><?php echo site_setting('mission_daily_spin_desc', t('mission_daily_spin_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 45%"></div>
            </div>
            <div class="mission-meta">
                <span>9 / 20</span>
                <span>+60₽</span>
            </div>
        </div>
        <div class="card mission-card">
            <strong><?php echo site_setting('mission_weekly_slots', t('mission_weekly_slots')); ?></strong>
            <p><?php echo site_setting('mission_weekly_slots_desc', t('mission_weekly_slots_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 70%"></div>
            </div>
            <div class="mission-meta">
                <span>14 / 20</span>
                <span>+250₽</span>
            </div>
        </div>
        <div class="card mission-card">
            <strong><?php echo site_setting('mission_minigame', t('mission_minigame')); ?></strong>
            <p><?php echo site_setting('mission_minigame_desc', t('mission_minigame_desc')); ?></p>
            <div class="progress">
                <div class="progress-bar" style="width: 30%"></div>
            </div>
            <div class="mission-meta">
                <span>2 / 6</span>
                <span>+120₽</span>
            </div>
        </div>
    </div>
</section>

<section class="section neo-section">
    <div class="section-header">
        <div>
            <h2>VIP-уровни</h2>
            <p class="muted">Переходите на следующий уровень, чтобы открыть больше привилегий.</p>
        </div>
    </div>
    <div class="cards vip-grid">
        <div class="card vip-card">
            <strong>Silver</strong>
            <p>Кешбек 3% • Еженедельные подарки</p>
        </div>
        <div class="card vip-card">
            <strong>Gold</strong>
            <p>Кешбек 5% • Персональные бонусы</p>
        </div>
        <div class="card vip-card">
            <strong>Platinum</strong>
            <p>Кешбек 8% • Приоритетная поддержка</p>
        </div>
    </div>
</section>
<?php render_footer(); ?>
