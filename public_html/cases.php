<?php
require __DIR__ . '/helpers.php';
render_header(t('cases_title'));
$cases = db()->query('SELECT * FROM cases WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
?>
<section class="section">
    <div class="section-header">
        <div>
            <h2><?php echo t('cases_title'); ?></h2>
            <p class="muted"><?php echo t('cases_subtitle'); ?></p>
        </div>
    </div>
    <div class="cards cases-grid">
        <?php foreach ($cases as $case): ?>
            <div class="card case-card" style="--case-accent: <?php echo htmlspecialchars($case['accent_color'], ENT_QUOTES); ?>;">
                <div class="case-card-header">
                    <div class="case-badge"><?php echo t('case_price'); ?>: <?php echo number_format((float) $case['price'], 2, '.', ' '); ?>₽</div>
                    <div class="case-orb"></div>
                </div>
                <h3><?php echo htmlspecialchars($case['name'], ENT_QUOTES); ?></h3>
                <p class="muted"><?php echo htmlspecialchars($case['description'] ?? '', ENT_QUOTES); ?></p>
                <a class="btn" href="/case.php?case=<?php echo urlencode($case['slug']); ?>"><?php echo t('case_open'); ?></a>
            </div>
        <?php endforeach; ?>
        <?php if (!$cases): ?>
            <div class="card">
                <p class="muted">Кейсы скоро появятся.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
