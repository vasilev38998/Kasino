<?php
require __DIR__ . '/../helpers.php';

$user = current_user();
if (!$user) {
    json_response(['error' => 'Требуется авторизация.'], 401);
}
$config = require __DIR__ . '/../config.php';
$limit = $config['security']['rate_limit']['spin'];
if (rate_limited('cases', $limit['window'], $limit['max'])) {
    json_response(['error' => 'Слишком много попыток.'], 429);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$caseId = (int) ($input['case_id'] ?? 0);
if ($caseId <= 0) {
    json_response(['error' => 'Некорректный кейс.'], 400);
}

$stmt = db()->prepare('SELECT id, price FROM cases WHERE id = ? AND is_active = 1');
$stmt->execute([$caseId]);
$case = $stmt->fetch();
if (!$case) {
    json_response(['error' => 'Кейс не найден.'], 404);
}

$price = (float) $case['price'];
$balance = user_balance((int) $user['id']);
if ($balance < $price) {
    json_response(['error' => t('insufficient_funds')], 400);
}

$items = db()->prepare('SELECT id, label, amount, weight FROM case_items WHERE case_id = ?');
$items->execute([$caseId]);
$rows = $items->fetchAll();
if (!$rows) {
    json_response(['error' => 'Кейс пуст.'], 400);
}

$total = 0;
foreach ($rows as $row) {
    $total += (int) $row['weight'];
}
if ($total <= 0) {
    json_response(['error' => 'Кейс настроен неверно.'], 400);
}

$roll = random_int(1, $total);
$current = 0;
$picked = $rows[0];
foreach ($rows as $row) {
    $current += (int) $row['weight'];
    if ($roll <= $current) {
        $picked = $row;
        break;
    }
}

$win = (float) $picked['amount'];
$newBalance = $balance - $price + $win;
db()->prepare('UPDATE balances SET balance = ? WHERE user_id = ?')
    ->execute([$newBalance, (int) $user['id']]);

$meta = [
    'case_id' => $caseId,
    'item_id' => (int) $picked['id'],
    'label' => $picked['label'],
    'amount' => $win,
    'weight' => (int) $picked['weight'],
];
db()->prepare('INSERT INTO case_open_logs (user_id, case_id, price, win, meta) VALUES (?, ?, ?, ?, ?)')
    ->execute([(int) $user['id'], $caseId, $price, $win, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

json_response([
    'win' => $win,
    'label' => $picked['label'],
    'balance' => $newBalance,
]);
