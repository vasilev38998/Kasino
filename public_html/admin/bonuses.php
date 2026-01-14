<?php
require __DIR__ . '/layout.php';
require_staff('bonuses');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $expiresAt = $_POST['expires_at'] ?? null;
    $expiresAt = $expiresAt !== '' ? $expiresAt : null;
    db()->prepare('INSERT INTO bonuses (user_id, type, amount, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$_POST['user_id'] ?? 0, $_POST['type'] ?? 'manual', $_POST['amount'] ?? 0, $expiresAt]);
}
$rows = db()->query('SELECT u.nickname, b.type, b.amount, b.expires_at FROM bonuses b JOIN users u ON u.id = b.user_id ORDER BY b.id DESC LIMIT 50')->fetchAll();
admin_header('Бонусы');
?>
<div class="section">
    <h2>Бонусы</h2>
    <form class="form-card" method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <label>ID пользователя</label>
        <input type="number" name="user_id" required>
        <label>Тип</label>
        <input type="text" name="type" value="manual" required>
        <label>Сумма</label>
        <input type="number" name="amount" required>
        <label>Срок действия (опционально)</label>
        <input type="date" name="expires_at">
        <button class="btn" type="submit">Начислить</button>
    </form>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo htmlspecialchars($row['nickname'], ENT_QUOTES); ?> • <?php echo $row['type']; ?> • <?php echo $row['amount']; ?>₽</div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
