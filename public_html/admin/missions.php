<?php
require __DIR__ . '/layout.php';
require_staff('missions');

function parse_datetime(?string $value): ?string
{
    if (!$value) {
        return null;
    }
    $time = strtotime($value);
    return $time ? date('Y-m-d H:i:s', $time) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $action = $_POST['action'] ?? '';
    $payload = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'type' => $_POST['type'] ?? 'slots_spins',
        'target_value' => (float) ($_POST['target_value'] ?? 0),
        'reward_amount' => (float) ($_POST['reward_amount'] ?? 0),
        'reward_type' => $_POST['reward_type'] ?? 'balance',
        'period' => $_POST['period'] ?? 'daily',
        'starts_at' => parse_datetime($_POST['starts_at'] ?? null),
        'ends_at' => parse_datetime($_POST['ends_at'] ?? null),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($action === 'create') {
        db()->prepare('INSERT INTO missions (name, description, type, target_value, reward_amount, reward_type, period, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $payload['name'],
                $payload['description'],
                $payload['type'],
                $payload['target_value'],
                $payload['reward_amount'],
                $payload['reward_type'],
                $payload['period'],
                $payload['starts_at'],
                $payload['ends_at'],
                $payload['is_active'],
            ]);
    } elseif ($action === 'update') {
        db()->prepare('UPDATE missions SET name = ?, description = ?, type = ?, target_value = ?, reward_amount = ?, reward_type = ?, period = ?, starts_at = ?, ends_at = ?, is_active = ? WHERE id = ?')
            ->execute([
                $payload['name'],
                $payload['description'],
                $payload['type'],
                $payload['target_value'],
                $payload['reward_amount'],
                $payload['reward_type'],
                $payload['period'],
                $payload['starts_at'],
                $payload['ends_at'],
                $payload['is_active'],
                (int) ($_POST['mission_id'] ?? 0),
            ]);
    }
}

$missions = db()->query('SELECT * FROM missions ORDER BY id DESC')->fetchAll();
admin_header('Миссии');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Миссии</h2>
            <p class="muted">Создавайте задания, отслеживайте прогресс и настраивайте награды.</p>
        </div>
    </div>
    <div class="card admin-form">
        <form method="post" class="admin-form-grid">
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="create">
            <label>Название</label>
            <input type="text" name="name" required>
            <label>Описание</label>
            <textarea name="description" rows="2" required></textarea>
            <label>Тип</label>
            <select name="type">
                <option value="slots_spins">Slots: спины</option>
                <option value="slots_bet">Slots: сумма ставок</option>
                <option value="slots_win">Slots: сумма выигрышей</option>
                <option value="minigame_plays">Мини-игры: попытки</option>
                <option value="minigame_win">Мини-игры: сумма выигрышей</option>
            </select>
            <label>Цель</label>
            <input type="number" name="target_value" step="0.01" value="0">
            <label>Награда</label>
            <input type="number" name="reward_amount" step="0.01" value="0">
            <label>Тип награды</label>
            <select name="reward_type">
                <option value="balance">Баланс</option>
            </select>
            <label>Период</label>
            <select name="period">
                <option value="daily">Ежедневная</option>
                <option value="weekly">Еженедельная</option>
                <option value="once">Постоянная</option>
            </select>
            <label>Старт</label>
            <input type="datetime-local" name="starts_at">
            <label>Финиш</label>
            <input type="datetime-local" name="ends_at">
            <label class="checkbox">
                <input type="checkbox" name="is_active" checked> Активна
            </label>
            <button class="btn" type="submit">Добавить миссию</button>
        </form>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>Миссия</div>
            <div>Цель</div>
            <div>Награда</div>
            <div>Период</div>
            <div>Статус</div>
            <div>Действия</div>
        </div>
        <?php foreach ($missions as $mission): ?>
            <form class="admin-table-row" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="mission_id" value="<?php echo (int) $mission['id']; ?>">
                <div>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($mission['name'], ENT_QUOTES); ?>">
                    <textarea name="description" rows="2"><?php echo htmlspecialchars($mission['description'], ENT_QUOTES); ?></textarea>
                    <select name="type">
                        <?php foreach (['slots_spins','slots_bet','slots_win','minigame_plays','minigame_win'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $mission['type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="number" name="target_value" step="0.01" value="<?php echo (float) $mission['target_value']; ?>">
                </div>
                <div>
                    <input type="number" name="reward_amount" step="0.01" value="<?php echo (float) $mission['reward_amount']; ?>">
                    <select name="reward_type">
                        <option value="balance" <?php echo $mission['reward_type'] === 'balance' ? 'selected' : ''; ?>>Баланс</option>
                    </select>
                </div>
                <div>
                    <select name="period">
                        <option value="daily" <?php echo $mission['period'] === 'daily' ? 'selected' : ''; ?>>daily</option>
                        <option value="weekly" <?php echo $mission['period'] === 'weekly' ? 'selected' : ''; ?>>weekly</option>
                        <option value="once" <?php echo $mission['period'] === 'once' ? 'selected' : ''; ?>>once</option>
                    </select>
                    <input type="datetime-local" name="starts_at" value="<?php echo $mission['starts_at'] ? date('Y-m-d\\TH:i', strtotime($mission['starts_at'])) : ''; ?>">
                    <input type="datetime-local" name="ends_at" value="<?php echo $mission['ends_at'] ? date('Y-m-d\\TH:i', strtotime($mission['ends_at'])) : ''; ?>">
                </div>
                <div>
                    <label class="checkbox">
                        <input type="checkbox" name="is_active" <?php echo $mission['is_active'] ? 'checked' : ''; ?>> Активна
                    </label>
                </div>
                <div>
                    <button class="btn" type="submit">Сохранить</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
