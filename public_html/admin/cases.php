<?php
require __DIR__ . '/layout.php';
require_staff('cms');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'save_case') {
        $caseId = (int) ($_POST['case_id'] ?? 0);
        $slug = trim($_POST['slug'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $accent = trim($_POST['accent_color'] ?? '#f9b233');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($caseId > 0) {
            db()->prepare('UPDATE cases SET slug = ?, name = ?, description = ?, price = ?, accent_color = ?, is_active = ? WHERE id = ?')
                ->execute([$slug, $name, $description, $price, $accent, $isActive, $caseId]);
        } else {
            db()->prepare('INSERT INTO cases (slug, name, description, price, accent_color, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$slug, $name, $description, $price, $accent, $isActive]);
        }
        $message = 'Кейс сохранён.';
    }
    if ($action === 'save_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $caseId = (int) ($_POST['case_id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $weight = (int) ($_POST['weight'] ?? 1);
        if ($itemId > 0) {
            db()->prepare('UPDATE case_items SET label = ?, amount = ?, weight = ? WHERE id = ?')
                ->execute([$label, $amount, $weight, $itemId]);
        } else {
            db()->prepare('INSERT INTO case_items (case_id, label, amount, weight) VALUES (?, ?, ?, ?)')
                ->execute([$caseId, $label, $amount, $weight]);
        }
        $message = 'Приз сохранён.';
    }
    if ($action === 'delete_item') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        db()->prepare('DELETE FROM case_items WHERE id = ?')->execute([$itemId]);
        $message = 'Приз удалён.';
    }
}

$cases = db()->query('SELECT * FROM cases ORDER BY id DESC')->fetchAll();
$itemsByCase = [];
$items = db()->query('SELECT * FROM case_items ORDER BY case_id, weight DESC')->fetchAll();
foreach ($items as $item) {
    $itemsByCase[$item['case_id']][] = $item;
}

admin_header('Кейсы');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Кейсы</h2>
            <p class="muted">Создавайте кейсы и настраивайте призы с вероятностями.</p>
        </div>
        <div class="admin-actions">
            <span class="admin-pill"><?php echo count($cases); ?> кейсов</span>
        </div>
    </div>
    <?php if ($message): ?>
        <div class="admin-alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card admin-support-card">
        <h3>Новый кейс</h3>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="save_case">
            <label>Slug</label>
            <input type="text" name="slug" placeholder="golden-case" required>
            <label>Название</label>
            <input type="text" name="name" required>
            <label>Описание</label>
            <textarea name="description" rows="2"></textarea>
            <label>Стоимость</label>
            <input type="number" step="0.01" name="price" required>
            <label>Цвет акцента</label>
            <input type="text" name="accent_color" value="#f9b233">
            <label><input type="checkbox" name="is_active" checked> Активен</label>
            <button class="btn" type="submit">Создать</button>
        </form>
    </div>

    <div class="admin-support-grid">
        <?php foreach ($cases as $case): ?>
            <div class="card admin-support-card">
                <div class="admin-support-header">
                    <div>
                        <strong><?php echo htmlspecialchars($case['name'], ENT_QUOTES); ?></strong>
                        <div class="muted small">#<?php echo $case['id']; ?> • <?php echo htmlspecialchars($case['slug'], ENT_QUOTES); ?></div>
                    </div>
                    <span class="admin-pill <?php echo $case['is_active'] ? 'is-success' : 'is-warning'; ?>">
                        <?php echo $case['is_active'] ? 'active' : 'inactive'; ?>
                    </span>
                </div>
                <form class="admin-form" method="post">
                    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="save_case">
                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($case['slug'], ENT_QUOTES); ?>" required>
                    <label>Название</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($case['name'], ENT_QUOTES); ?>" required>
                    <label>Описание</label>
                    <textarea name="description" rows="2"><?php echo htmlspecialchars($case['description'] ?? '', ENT_QUOTES); ?></textarea>
                    <label>Стоимость</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $case['price']; ?>" required>
                    <label>Цвет акцента</label>
                    <input type="text" name="accent_color" value="<?php echo htmlspecialchars($case['accent_color'], ENT_QUOTES); ?>">
                    <label><input type="checkbox" name="is_active" <?php echo $case['is_active'] ? 'checked' : ''; ?>> Активен</label>
                    <button class="btn" type="submit">Сохранить кейс</button>
                </form>

                <h4>Призы</h4>
                <?php foreach ($itemsByCase[$case['id']] ?? [] as $item): ?>
                    <form class="admin-inline-form" method="post">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="save_item">
                        <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <input type="text" name="label" value="<?php echo htmlspecialchars($item['label'], ENT_QUOTES); ?>" placeholder="Название">
                        <input type="number" step="0.01" name="amount" value="<?php echo $item['amount']; ?>" placeholder="Сумма">
                        <input type="number" name="weight" value="<?php echo $item['weight']; ?>" placeholder="Шанс">
                        <button class="btn" type="submit">Сохранить</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <button class="btn ghost" type="submit">Удалить</button>
                    </form>
                <?php endforeach; ?>

                <form class="admin-form" method="post">
                    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="save_item">
                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                    <label>Новый приз</label>
                    <input type="text" name="label" placeholder="Название приза">
                    <input type="number" step="0.01" name="amount" placeholder="Сумма">
                    <input type="number" name="weight" placeholder="Вес" value="1">
                    <button class="btn ghost" type="submit">Добавить приз</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
