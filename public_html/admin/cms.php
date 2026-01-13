<?php
require __DIR__ . '/layout.php';
require_staff('cms');
$fields = [
    'hero_title' => 'Заголовок героя',
    'hero_subtitle' => 'Подзаголовок героя',
    'bonus_daily_text' => 'Текст ежедневного бонуса',
    'bonus_welcome_text' => 'Текст приветственного бонуса',
    'bonus_cashback_text' => 'Текст кешбека',
    'missions_title' => 'Заголовок миссий',
    'missions_subtitle' => 'Подзаголовок миссий',
    'mission_daily_spin' => 'Название дневной миссии',
    'mission_daily_spin_desc' => 'Описание дневной миссии',
    'mission_weekly_slots' => 'Название недельной миссии',
    'mission_weekly_slots_desc' => 'Описание недельной миссии',
    'mission_minigame' => 'Название мини-турнира',
    'mission_minigame_desc' => 'Описание мини-турнира',
    'referral_title' => 'Заголовок рефералки',
    'referral_reward' => 'Заголовок награды рефералки',
    'referral_desc' => 'Описание рефералки',
    'promotions_title' => 'Заголовок акций',
    'promo_vip_title' => 'Заголовок VIP турнира',
    'promo_vip_desc' => 'Описание VIP турнира',
    'promo_streak_title' => 'Заголовок серии побед',
    'promo_streak_desc' => 'Описание серии побед',
    'promo_codes_title' => 'Заголовок промокодов',
    'promo_codes_desc' => 'Описание промокодов',
    'promo_codes_list' => 'Список промокодов (через запятую)',
    'terms' => 'Правила',
    'privacy' => 'Политика конфиденциальности',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf'] ?? '')) {
        echo '<p>Ошибка безопасности.</p>';
        exit;
    }
    $name = $_POST['name'] ?? '';
    if (!array_key_exists($name, $fields)) {
        echo '<p>Недоступное поле.</p>';
        exit;
    }
    db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)')
        ->execute([$name, $_POST['value'] ?? '']);
}
$placeholders = implode(', ', array_fill(0, count($fields), '?'));
$stmt = db()->prepare("SELECT name, value FROM settings WHERE name IN ({$placeholders})");
$stmt->execute(array_keys($fields));
$existing = [];
foreach ($stmt->fetchAll() as $row) {
    $existing[$row['name']] = $row['value'];
}
admin_header('CMS');
?>
<div class="section">
    <h2>Контент</h2>
    <?php foreach ($fields as $name => $label): ?>
        <form class="form-card" method="post">
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="name" value="<?php echo $name; ?>">
            <label><?php echo $label; ?></label>
            <textarea name="value" rows="4" required><?php echo htmlspecialchars($existing[$name] ?? '', ENT_QUOTES); ?></textarea>
            <button class="btn" type="submit">Сохранить</button>
        </form>
    <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
