<?php
require __DIR__ . '/helpers.php';
render_header('Kasino Lux');
$slots = slots_catalog();
?>
<section class="hero">
    <div>
        <h1><?php echo t('hero_title'); ?></h1>
        <p><?php echo t('hero_subtitle'); ?></p>
        <div class="hero-actions">
            <a class="btn" href="/slots.php"><?php echo t('cta_play'); ?></a>
            <a class="btn ghost" href="/register.php"><?php echo t('cta_register'); ?></a>
            <button class="btn ghost" data-install hidden>Установить PWA</button>
        </div>
    </div>
    <div class="hero-card">
        <div class="badge"><?php echo t('balance'); ?>: <span data-balance>0₽</span></div>
        <p>RTP Live: 96.4% • Volatility: High</p>
        <div class="cards">
            <div class="skeleton"></div>
            <div class="skeleton"></div>
        </div>
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
            <p>Забирайте награду каждые 24 часа.</p>
        </div>
        <div class="card">
            <strong><?php echo t('bonus_welcome'); ?></strong>
            <p>До 500₽ + вейджер 20x.</p>
        </div>
        <div class="card">
            <strong><?php echo t('bonus_cashback'); ?></strong>
            <p>Возврат до 5% каждую неделю.</p>
        </div>
    </div>
</section>
<?php render_footer(); ?>
