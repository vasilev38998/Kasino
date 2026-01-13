<?php
require __DIR__ . '/helpers.php';
render_header('Kasino Lux');
$user = current_user();
$slots = slots_catalog();
$config = require __DIR__ . '/config.php';
$heroSlots = array_slice($slots, 0, 4);
?>
<section class="hero hero-glow">
    <div>
        <div class="hero-badge">Kasino Lux • VIP Club</div>
        <h1><?php echo site_setting('hero_title', t('hero_title')); ?></h1>
        <p><?php echo site_setting('hero_subtitle', t('hero_subtitle')); ?></p>
        <div class="hero-actions">
            <?php if ($user): ?>
                <a class="btn" href="/slots.php"><?php echo t('cta_play'); ?></a>
                <a class="btn ghost" href="/profile.php"><?php echo t('nav_profile'); ?></a>
            <?php else: ?>
                <a class="btn" href="/register.php"><?php echo t('cta_register'); ?></a>
                <a class="btn ghost" href="/login.php"><?php echo t('nav_login'); ?></a>
            <?php endif; ?>
            <button class="btn ghost" data-install hidden>Установить PWA</button>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <span>RTP Live</span>
                <strong>96.4%</strong>
            </div>
            <div class="hero-stat">
                <span>Пользователей</span>
                <strong>12 840</strong>
            </div>
            <div class="hero-stat">
                <span>Бонусы</span>
                <strong>500₽</strong>
            </div>
        </div>
    </div>
    <div class="hero-card">
        <?php if ($user): ?>
            <div class="badge"><?php echo t('balance'); ?>: <span data-balance>0₽</span></div>
            <p>RTP Live: 96.4% • Volatility: High</p>
        <?php else: ?>
            <div class="badge">Гость</div>
            <p>Зарегистрируйтесь, чтобы видеть баланс, бонусы и персональные предложения.</p>
        <?php endif; ?>
        <div class="hero-grid">
            <?php foreach ($heroSlots as $slot): ?>
                <div class="hero-slot">
                    <img src="<?php echo $slot['icon']; ?>" alt="<?php echo $slot['name']; ?>">
                    <div>
                        <strong><?php echo $slot['name']; ?></strong>
                        <span>RTP <?php echo $slot['rtp']; ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$user): ?>
            <div class="hero-cta">
                <a class="btn" href="/register.php"><?php echo t('cta_register'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>
<section class="section">
    <h2><?php echo t('section_top'); ?></h2>
    <div class="cards">
        <?php foreach ($slots as $slot): ?>
            <div class="card slot-card">
                <img src="<?php echo $slot['icon']; ?>" alt="<?php echo $slot['name']; ?>">
                <strong><?php echo $slot['name']; ?></strong>
                <span>RTP <?php echo $slot['rtp']; ?>%</span>
                <a class="btn" href="/slot.php?game=<?php echo $slot['slug']; ?>"><?php echo t('slot_play'); ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<section class="section">
    <h2>VIP-уровни</h2>
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
<section class="section">
    <h2><?php echo t('section_live'); ?></h2>
    <div class="cards" data-live-feed>
        <div class="card">Nova • Aurora Cascade • 1400₽</div>
        <div class="card">Raven • Cosmic Cluster • 980₽</div>
        <div class="card">Luna • Dragon Sticky Wilds • 2100₽</div>
    </div>
</section>
<section class="section">
    <h2><?php echo t('section_bonuses'); ?></h2>
    <div class="grid-two">
        <div class="card">
            <strong><?php echo t('bonus_daily'); ?></strong>
            <p><?php echo site_setting('bonus_daily_text', 'Забирайте награду каждые 24 часа.'); ?></p>
        </div>
        <div class="card">
            <strong><?php echo t('bonus_welcome'); ?></strong>
            <p><?php echo site_setting('bonus_welcome_text', 'До 500₽ + вейджер 20x.'); ?></p>
        </div>
        <div class="card">
            <strong><?php echo t('bonus_cashback'); ?></strong>
            <p><?php echo site_setting('bonus_cashback_text', 'Возврат до 5% каждую неделю.'); ?></p>
        </div>
    </div>
</section>
<section class="section">
    <h2><?php echo site_setting('missions_title', t('missions_title')); ?></h2>
    <p class="muted"><?php echo site_setting('missions_subtitle', t('missions_subtitle')); ?></p>
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
<?php render_footer(); ?>
