<?php
require __DIR__ . '/helpers.php';
require_login();

$slug = $_GET['case'] ?? '';
$stmt = db()->prepare('SELECT * FROM cases WHERE slug = ? AND is_active = 1');
$stmt->execute([$slug]);
$case = $stmt->fetch();
if (!$case) {
    render_header('Case not found');
    echo '<div class="section"><div class="card">Кейс не найден.</div></div>';
    render_footer();
    exit;
}
$itemsStmt = db()->prepare('SELECT id, label, amount, weight FROM case_items WHERE case_id = ? ORDER BY weight DESC');
$itemsStmt->execute([$case['id']]);
$items = $itemsStmt->fetchAll();

render_header($case['name']);
?>
<section class="section case-detail" style="--case-accent: <?php echo htmlspecialchars($case['accent_color'], ENT_QUOTES); ?>;">
    <div class="case-hero">
        <div>
            <a class="muted small" href="/cases.php">← <?php echo t('cases_title'); ?></a>
            <h2><?php echo htmlspecialchars($case['name'], ENT_QUOTES); ?></h2>
            <p class="muted"><?php echo htmlspecialchars($case['description'] ?? '', ENT_QUOTES); ?></p>
            <div class="case-price"><?php echo t('case_price'); ?>: <strong><?php echo number_format((float) $case['price'], 2, '.', ' '); ?>₽</strong></div>
        </div>
        <div class="case-orb large"></div>
    </div>

    <div class="case-open-panel" data-case-items='<?php echo htmlspecialchars(json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>' data-case-id="<?php echo $case['id']; ?>" data-case-win="<?php echo t('case_win'); ?>">
        <div class="case-reel" data-case-reel>
            <div class="case-reel-track"></div>
        </div>
        <div class="case-result" data-case-result><?php echo t('case_contains'); ?></div>
        <button class="btn case-open" data-case-open><?php echo t('case_open'); ?></button>
    </div>

    <h3><?php echo t('case_contains'); ?></h3>
    <div class="case-items">
        <?php foreach ($items as $item): ?>
            <div class="case-item">
                <div class="case-item-label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES); ?></div>
                <div class="case-item-amount"><?php echo number_format((float) $item['amount'], 2, '.', ' '); ?>₽</div>
                <div class="case-item-weight">Вес: <?php echo (int) $item['weight']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
