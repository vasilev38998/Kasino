<?php
require __DIR__ . '/layout.php';
require_staff('tournaments');

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
        'game_type' => $_POST['game_type'] ?? 'slots',
        'metric' => $_POST['metric'] ?? 'win',
        'entry_fee' => (float) ($_POST['entry_fee'] ?? 0),
        'prize_pool' => (float) ($_POST['prize_pool'] ?? 0),
        'starts_at' => parse_datetime($_POST['starts_at'] ?? null),
        'ends_at' => parse_datetime($_POST['ends_at'] ?? null),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($action === 'create') {
        db()->prepare('INSERT INTO tournaments (name, description, game_type, metric, entry_fee, prize_pool, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $payload['name'],
                $payload['description'],
                $payload['game_type'],
                $payload['metric'],
                $payload['entry_fee'],
                $payload['prize_pool'],
                $payload['starts_at'],
                $payload['ends_at'],
                $payload['is_active'],
            ]);
    } elseif ($action === 'update') {
        db()->prepare('UPDATE tournaments SET name = ?, description = ?, game_type = ?, metric = ?, entry_fee = ?, prize_pool = ?, starts_at = ?, ends_at = ?, is_active = ? WHERE id = ?')
            ->execute([
                $payload['name'],
                $payload['description'],
                $payload['game_type'],
                $payload['metric'],
                $payload['entry_fee'],
                $payload['prize_pool'],
                $payload['starts_at'],
                $payload['ends_at'],
                $payload['is_active'],
                (int) ($_POST['tournament_id'] ?? 0),
            ]);
    }
}

$tournaments = db()->query('SELECT * FROM tournaments ORDER BY starts_at DESC')->fetchAll();
admin_header('Турниры');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Турниры</h2>
            <p class="muted">Создавайте турнирные гонки, задавайте фонд и метрику очков.</p>
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
            <label>Тип игры</label>
            <select name="game_type">
                <option value="slots">Слоты</option>
                <option value="minigames">Мини-игры</option>
                <option value="any">Любые</option>
            </select>
            <label>Метрика</label>
            <select name="metric">
                <option value="win">Выигрыш</option>
                <option value="bet">Оборот ставок</option>
                <option value="spins">Количество игр</option>
            </select>
            <label>Входной взнос</label>
            <input type="number" name="entry_fee" step="0.01" value="0">
            <label>Призовой фонд</label>
            <input type="number" name="prize_pool" step="0.01" value="0">
            <label>Старт</label>
            <input type="datetime-local" name="starts_at">
            <label>Финиш</label>
            <input type="datetime-local" name="ends_at">
            <label class="checkbox">
                <input type="checkbox" name="is_active" checked> Активен
            </label>
            <button class="btn" type="submit">Добавить турнир</button>
        </form>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>Турнир</div>
            <div>Фонд</div>
            <div>Метрика</div>
            <div>Даты</div>
            <div>Статус</div>
            <div>Действия</div>
        </div>
        <?php foreach ($tournaments as $tournament): ?>
            <form class="admin-table-row" method="post">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="tournament_id" value="<?php echo (int) $tournament['id']; ?>">
                <div>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($tournament['name'], ENT_QUOTES); ?>">
                    <textarea name="description" rows="2"><?php echo htmlspecialchars($tournament['description'], ENT_QUOTES); ?></textarea>
                    <select name="game_type">
                        <?php foreach (['slots','minigames','any'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $tournament['game_type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="number" name="prize_pool" step="0.01" value="<?php echo (float) $tournament['prize_pool']; ?>">
                    <input type="number" name="entry_fee" step="0.01" value="<?php echo (float) $tournament['entry_fee']; ?>">
                </div>
                <div>
                    <select name="metric">
                        <?php foreach (['win','bet','spins'] as $metric): ?>
                            <option value="<?php echo $metric; ?>" <?php echo $tournament['metric'] === $metric ? 'selected' : ''; ?>><?php echo $metric; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="datetime-local" name="starts_at" value="<?php echo $tournament['starts_at'] ? date('Y-m-d\\TH:i', strtotime($tournament['starts_at'])) : ''; ?>">
                    <input type="datetime-local" name="ends_at" value="<?php echo $tournament['ends_at'] ? date('Y-m-d\\TH:i', strtotime($tournament['ends_at'])) : ''; ?>">
                </div>
                <div>
                    <label class="checkbox">
                        <input type="checkbox" name="is_active" <?php echo $tournament['is_active'] ? 'checked' : ''; ?>> Активен
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
