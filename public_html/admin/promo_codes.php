<?php
require __DIR__ . '/layout.php';
require_staff('promo');
$message = '';
function generate_promo_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $segment = '';
        for ($j = 0; $j < 4; $j++) {
            $segment .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $parts[] = $segment;
    }
    return implode('-', $parts);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $amount = (float) ($_POST['amount'] ?? 0);
        if (!preg_match('/^(?:[A-Z0-9]{4}-){3}[A-Z0-9]{4}$/', $code)) {
            $message = 'Неверный формат промокода.';
        } elseif ($amount <= 0) {
            $message = 'Сумма некорректна.';
        } else {
            db()->prepare('INSERT INTO promo_codes (code, amount, max_uses) VALUES (?, ?, 1)')
                ->execute([$code, $amount]);
            $message = 'Промокод создан.';
        }
    }
    if ($action === 'generate') {
        $count = max(1, min(100, (int) ($_POST['count'] ?? 1)));
        $amount = (float) ($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $message = 'Сумма некорректна.';
        } else {
            $insert = db()->prepare('INSERT INTO promo_codes (code, amount, max_uses) VALUES (?, ?, 1)');
            $check = db()->prepare('SELECT COUNT(*) AS total FROM promo_codes WHERE code = ?');
            $created = [];
            for ($i = 0; $i < $count; $i++) {
                $tries = 0;
                do {
                    $code = generate_promo_code();
                    $check->execute([$code]);
                    $exists = (int) $check->fetchColumn() > 0;
                    $tries++;
                } while ($exists && $tries < 5);
                if ($exists) {
                    continue;
                }
                $insert->execute([$code, $amount]);
                $created[] = $code;
            }
            $message = $created ? 'Сгенерировано: ' . implode(', ', $created) : 'Не удалось создать новые промокоды.';
        }
    }
}
$rows = db()->query('SELECT code, amount, max_uses, used FROM promo_codes ORDER BY id DESC LIMIT 50')->fetchAll();
admin_header('Промокоды');
?>
<div class="section">
    <h2>Промокоды</h2>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
    <form class="form-card" method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="create">
        <label>Код</label>
        <input type="text" name="code" placeholder="XXXX-XXXX-XXXX-XXXX" required>
        <label>Сумма</label>
        <input type="number" name="amount" required>
        <button class="btn" type="submit">Создать</button>
    </form>
    <form class="form-card" method="post">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="generate">
        <label>Количество</label>
        <input type="number" name="count" min="1" max="100" value="5" required>
        <label>Сумма</label>
        <input type="number" name="amount" required>
        <button class="btn" type="submit">Сгенерировать</button>
    </form>
    <div class="cards">
        <?php foreach ($rows as $row): ?>
            <div class="card"><?php echo $row['code']; ?> • <?php echo $row['amount']; ?>₽ • <?php echo $row['used']; ?>/<?php echo $row['max_uses']; ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php admin_footer(); ?>
