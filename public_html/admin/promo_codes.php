<?php
require __DIR__ . '/layout.php';
require_staff('promo');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    db()->prepare('INSERT INTO promo_codes (code, amount, max_uses) VALUES (?, ?, ?)')
        ->execute([$_POST['code'] ?? '', $_POST['amount'] ?? 0, $_POST['max_uses'] ?? 0]);
}
$rows = db()->query('SELECT code, amount, max_uses, used FROM promo_codes ORDER BY id DESC LIMIT 50')->fetchAll();
admin_header('Промокоды');
?>
<div class="section">
    <h2>Промокоды</h2>
    <form class="form-card" method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label>Код</label>
        <input type="text" name="code" required>
        <label>Сумма</label>
        <input type="number" name="amount" required>
        <label>Лимит</label>
        <input type="number" name="max_uses" required>
        <button class="btn" type="submit">Создать</button>
    </form>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo $row['code']; ?> • <?php echo $row['amount']; ?>₽ • <?php echo $row['used']; ?>/<?php echo $row['max_uses']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
