<?php
require __DIR__ . '/helpers.php';
logout();
render_header('Выход');
?>
<section class="section logout-section" data-logout>
    <div class="card">
        <h2>Выходим из аккаунта...</h2>
        <p>Подождите пару секунд, мы очищаем данные.</p>
    </div>
</section>
<?php render_footer(); ?>
