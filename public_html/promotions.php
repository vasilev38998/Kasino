<?php
require __DIR__ . '/helpers.php';
render_header(t('promotions_title'));
?>
<section class="section">
    <h2><?php echo t('promotions_title'); ?></h2>
    <div class="cards">
        <div class="card">VIP-турнир недели: призовой фонд 50 000₽</div>
        <div class="card">Фриспины за серию побед</div>
        <div class="card">Эксклюзивные промокоды для ежедневных бонусов</div>
    </div>
</section>
<?php render_footer(); ?>
