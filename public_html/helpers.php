<?php
$config = require __DIR__ . '/config.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
ini_set('session.cookie_secure', $isSecure ? '1' : '0');

session_name($config['security']['session_name']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

function db(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['name'],
        $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_validate(?string $token): bool
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $token);
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] === 'banned') {
        logout();
        return null;
    }
    return $user;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function rate_limited(string $key, int $window, int $max): bool
{
    $now = time();
    $_SESSION['rate'] ??= [];
    $_SESSION['rate'][$key] ??= [];
    $_SESSION['rate'][$key] = array_filter($_SESSION['rate'][$key], fn($ts) => $ts > $now - $window);
    if (count($_SESSION['rate'][$key]) >= $max) {
        return true;
    }
    $_SESSION['rate'][$key][] = $now;
    return false;
}

function lang(): string
{
    $config = require __DIR__ . '/config.php';
    $user = current_user();
    if ($user && $user['language']) {
        return $user['language'];
    }
    if (!empty($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    return $config['site']['default_language'];
}

function set_lang(string $lang): void
{
    $_SESSION['lang'] = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';
}

function t(string $key): string
{
    $translations = require __DIR__ . '/translations.php';
    $language = lang();
    if (!isset($translations[$language][$key])) {
        return $translations['ru'][$key] ?? $key;
    }
    return $translations[$language][$key];
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function render_header(string $title): void
{
    $config = require __DIR__ . '/config.php';
    $language = lang();
    echo "<!doctype html>\n";
    echo "<html lang=\"{$language}\">\n";
    echo "<head>\n";
    echo "<meta charset=\"utf-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo "<meta name=\"theme-color\" content=\"{$config['pwa']['theme_color']}\">\n";
    echo "<link rel=\"manifest\" href=\"/manifest.json\">\n";
    echo "<link rel=\"stylesheet\" href=\"/assets/css/style.css\">\n";
    echo "<script defer src=\"/assets/js/app.js\"></script>\n";
    echo "<title>{$title}</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<div class=\"app\">\n";
    echo "<header class=\"topbar\">\n";
    echo "<div class=\"logo\">Kasino <span>Lux</span></div>\n";
    echo "<nav class=\"nav\">\n";
    echo "<a href=\"/index.php\">" . t('nav_home') . "</a>\n";
    echo "<a href=\"/slots.php\">" . t('nav_slots') . "</a>\n";
    echo "<a href=\"/promotions.php\">" . t('nav_promotions') . "</a>\n";
    echo "<a href=\"/leaderboard.php\">" . t('nav_leaderboard') . "</a>\n";
    if (current_user()) {
        echo "<a href=\"/notifications.php\">" . t('nav_notifications') . "</a>\n";
        echo "<a href=\"/wallet.php\">" . t('nav_wallet') . "</a>\n";
    } else {
        echo "<a href=\"/login.php\">" . t('nav_login') . "</a>\n";
        echo "<a href=\"/register.php\">" . t('nav_register') . "</a>\n";
    }
    echo "<a href=\"/minigames.php\">" . t('nav_minigames') . "</a>\n";
    echo "<a href=\"/support.php\">" . t('nav_support') . "</a>\n";
    echo "</nav>\n";
    echo "<div class=\"topbar-actions\">\n";
    if (current_user()) {
        echo "<a class=\"btn\" href=\"/profile.php\">" . t('nav_profile') . "</a>\n";
        echo "<a class=\"btn ghost\" href=\"/logout.php\">" . t('nav_logout') . "</a>\n";
    } else {
        echo "<a class=\"btn ghost\" href=\"/login.php\">" . t('nav_login') . "</a>\n";
        echo "<a class=\"btn\" href=\"/register.php\">" . t('nav_register') . "</a>\n";
    }
    echo "<div class=\"lang-switch\" data-lang=\"{$language}\">\n";
    echo "<button data-lang-btn=\"ru\">RU</button>\n";
    echo "<button data-lang-btn=\"en\">EN</button>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</header>\n";
}

function render_footer(): void
{
    echo "<footer class=\"footer\">\n";
    echo "<div class=\"footer-links\">\n";
    echo "<a href=\"/terms.php\">" . t('nav_terms') . "</a>\n";
    echo "<a href=\"/privacy.php\">" . t('nav_privacy') . "</a>\n";
    echo "</div>\n";
    echo "<p class=\"legal\">" . t('legal_notice') . "</p>\n";
    echo "</footer>\n";
    echo "</div>\n";
    echo "<div id=\"toast\" class=\"toast\"></div>\n";
    echo "</body>\n";
    echo "</html>\n";
}

function slots_catalog(): array
{
    return [
        [
            'slug' => 'aurora-cascade',
            'name' => 'Aurora Cascade',
            'icon' => '/assets/icons/slot-aurora.svg',
            'rtp' => 96.2,
            'theme' => 'aurora',
            'mechanic' => 'Каскады + линии',
            'type' => 'cascade',
            'win_type' => 'row',
            'cols' => 6,
            'rows' => 5,
            'symbols' => [
                'aurora_crystal',
                'aurora_star',
                'aurora_comet',
                'aurora_ring',
                'aurora_orb',
                'aurora_shard',
                'aurora_wave',
                'aurora_prism',
            ],
            'symbol_labels' => [
                'aurora_crystal' => 'Кристалл',
                'aurora_star' => 'Звезда',
                'aurora_comet' => 'Комета',
                'aurora_ring' => 'Кольцо',
                'aurora_orb' => 'Сфера',
                'aurora_shard' => 'Осколок',
                'aurora_wave' => 'Волна',
                'aurora_prism' => 'Призма',
            ],
            'weights' => [18, 6, 14, 12, 16, 10, 12, 8],
            'payouts' => [
                ['count' => 6, 'multiplier' => 0.5],
                ['count' => 8, 'multiplier' => 0.9],
                ['count' => 10, 'multiplier' => 1.4],
            ],
            'rare_symbols' => ['aurora_prism', 'aurora_star'],
            'rare_bonus' => 0.4,
            'scatter' => 'aurora_star',
        ],
        [
            'slug' => 'cosmic-cluster',
            'name' => 'Cosmic Cluster',
            'icon' => '/assets/icons/slot-cosmic.svg',
            'rtp' => 95.7,
            'theme' => 'cosmic',
            'mechanic' => 'Диагональные цепи',
            'type' => 'ways',
            'win_type' => 'diagonal',
            'cols' => 5,
            'rows' => 4,
            'symbols' => [
                'cosmic_planet',
                'cosmic_moon',
                'cosmic_nova',
                'cosmic_saturn',
                'cosmic_void',
                'cosmic_ray',
                'cosmic_asteroid',
                'cosmic_pulse',
            ],
            'symbol_labels' => [
                'cosmic_planet' => 'Планета',
                'cosmic_moon' => 'Луна',
                'cosmic_nova' => 'Нова',
                'cosmic_saturn' => 'Сатурн',
                'cosmic_void' => 'Вихрь',
                'cosmic_ray' => 'Луч',
                'cosmic_asteroid' => 'Астероид',
                'cosmic_pulse' => 'Импульс',
            ],
            'weights' => [16, 14, 6, 10, 12, 14, 12, 16],
            'payouts' => [
                ['count' => 5, 'multiplier' => 0.6],
                ['count' => 7, 'multiplier' => 1.1],
                ['count' => 9, 'multiplier' => 1.6],
            ],
            'rare_symbols' => ['cosmic_nova'],
            'rare_bonus' => 0.5,
            'scatter' => 'cosmic_nova',
        ],
        [
            'slug' => 'dragon-sticky',
            'name' => 'Dragon Sticky Wilds',
            'icon' => '/assets/icons/slot-dragon.svg',
            'rtp' => 94.9,
            'theme' => 'dragon',
            'mechanic' => 'Sticky + кромка',
            'type' => 'sticky',
            'win_type' => 'edge',
            'cols' => 6,
            'rows' => 5,
            'symbols' => [
                'dragon_scale',
                'dragon_claw',
                'dragon_orb',
                'dragon_ember',
                'dragon_flame',
                'dragon_banner',
                'dragon_horn',
                'dragon_eye',
            ],
            'symbol_labels' => [
                'dragon_scale' => 'Чешуя',
                'dragon_claw' => 'Коготь',
                'dragon_orb' => 'Сфера',
                'dragon_ember' => 'Искра',
                'dragon_flame' => 'Пламя',
                'dragon_banner' => 'Штандарт',
                'dragon_horn' => 'Рог',
                'dragon_eye' => 'Око',
            ],
            'weights' => [18, 14, 12, 10, 6, 14, 10, 8],
            'payouts' => [
                ['count' => 6, 'multiplier' => 0.55],
                ['count' => 8, 'multiplier' => 1.0],
                ['count' => 10, 'multiplier' => 1.6],
            ],
            'rare_symbols' => ['dragon_flame', 'dragon_eye'],
            'rare_bonus' => 0.45,
            'scatter' => 'dragon_flame',
        ],
        [
            'slug' => 'sky-titans',
            'name' => 'Sky Titans',
            'icon' => '/assets/icons/slot-sky.svg',
            'rtp' => 96.4,
            'theme' => 'sky',
            'mechanic' => 'Scatter Multiplier',
            'type' => 'scatter',
            'win_type' => 'count',
            'cols' => 6,
            'rows' => 5,
            'symbols' => [
                'sky_bolt',
                'sky_cloud',
                'sky_wing',
                'sky_sun',
                'sky_rain',
                'sky_titan',
                'sky_gale',
                'sky_crown',
            ],
            'symbol_labels' => [
                'sky_bolt' => 'Молния',
                'sky_cloud' => 'Облако',
                'sky_wing' => 'Крыло',
                'sky_sun' => 'Солнце',
                'sky_rain' => 'Дождь',
                'sky_titan' => 'Титан',
                'sky_gale' => 'Шквал',
                'sky_crown' => 'Корона',
            ],
            'weights' => [16, 14, 14, 10, 12, 8, 12, 6],
            'payouts' => [
                ['count' => 6, 'multiplier' => 0.5],
                ['count' => 8, 'multiplier' => 0.95],
                ['count' => 10, 'multiplier' => 1.5],
            ],
            'rare_symbols' => ['sky_crown', 'sky_titan'],
            'rare_bonus' => 0.4,
            'scatter' => 'sky_bolt',
        ],
        [
            'slug' => 'sugar-bloom',
            'name' => 'Sugar Bloom',
            'icon' => '/assets/icons/slot-sugar.svg',
            'rtp' => 96.1,
            'theme' => 'sugar',
            'mechanic' => 'Cluster Pays',
            'type' => 'cluster',
            'win_type' => 'cluster',
            'cols' => 7,
            'rows' => 7,
            'symbols' => [
                'sugar_macaron',
                'sugar_candy',
                'sugar_lolli',
                'sugar_jelly',
                'sugar_cupcake',
                'sugar_sprinkle',
                'sugar_heart',
                'sugar_star',
            ],
            'symbol_labels' => [
                'sugar_macaron' => 'Макарон',
                'sugar_candy' => 'Конфета',
                'sugar_lolli' => 'Леденец',
                'sugar_jelly' => 'Желе',
                'sugar_cupcake' => 'Кекс',
                'sugar_sprinkle' => 'Посыпка',
                'sugar_heart' => 'Сердце',
                'sugar_star' => 'Звезда',
            ],
            'weights' => [16, 14, 12, 14, 10, 12, 10, 6],
            'payouts' => [
                ['count' => 8, 'multiplier' => 0.7],
                ['count' => 10, 'multiplier' => 1.2],
                ['count' => 12, 'multiplier' => 1.8],
            ],
            'rare_symbols' => ['sugar_star', 'sugar_heart'],
            'rare_bonus' => 0.5,
            'scatter' => 'sugar_star',
        ],
        [
            'slug' => 'zenith-gems',
            'name' => 'Zenith Gems',
            'icon' => '/assets/icons/slot-zenith.svg',
            'rtp' => 96.3,
            'theme' => 'zenith',
            'mechanic' => 'Колонны кристаллов',
            'type' => 'burst',
            'win_type' => 'column',
            'cols' => 5,
            'rows' => 5,
            'symbols' => [
                'zenith_gem',
                'zenith_prism',
                'zenith_triangle',
                'zenith_hex',
                'zenith_orb',
                'zenith_shard',
                'zenith_beam',
                'zenith_crown',
            ],
            'symbol_labels' => [
                'zenith_gem' => 'Гем',
                'zenith_prism' => 'Призма',
                'zenith_triangle' => 'Треугольник',
                'zenith_hex' => 'Гекса',
                'zenith_orb' => 'Сфера',
                'zenith_shard' => 'Осколок',
                'zenith_beam' => 'Луч',
                'zenith_crown' => 'Корона',
            ],
            'weights' => [16, 12, 12, 14, 14, 10, 10, 6],
            'payouts' => [
                ['count' => 5, 'multiplier' => 0.6],
                ['count' => 7, 'multiplier' => 1.1],
                ['count' => 9, 'multiplier' => 1.7],
            ],
            'rare_symbols' => ['zenith_prism', 'zenith_crown'],
            'rare_bonus' => 0.45,
            'scatter' => 'zenith_prism',
        ],
        [
            'slug' => 'orbit-jewels',
            'name' => 'Orbit Jewels',
            'icon' => '/assets/icons/slot-orbit.svg',
            'rtp' => 95.9,
            'theme' => 'orbit',
            'mechanic' => 'Угловые ставки',
            'type' => 'orbit',
            'win_type' => 'corner',
            'cols' => 6,
            'rows' => 4,
            'symbols' => [
                'orbit_planet',
                'orbit_ring',
                'orbit_meteor',
                'orbit_star',
                'orbit_core',
                'orbit_satellite',
                'orbit_comet',
                'orbit_wave',
            ],
            'symbol_labels' => [
                'orbit_planet' => 'Планета',
                'orbit_ring' => 'Кольцо',
                'orbit_meteor' => 'Метеор',
                'orbit_star' => 'Звезда',
                'orbit_core' => 'Ядро',
                'orbit_satellite' => 'Спутник',
                'orbit_comet' => 'Комета',
                'orbit_wave' => 'Орбита',
            ],
            'weights' => [16, 12, 12, 6, 14, 10, 10, 14],
            'payouts' => [
                ['count' => 5, 'multiplier' => 0.65],
                ['count' => 7, 'multiplier' => 1.2],
                ['count' => 9, 'multiplier' => 1.8],
            ],
            'rare_symbols' => ['orbit_star', 'orbit_comet'],
            'rare_bonus' => 0.5,
            'scatter' => 'orbit_star',
        ],
        [
            'slug' => 'reef-relay',
            'name' => 'Reef Relay',
            'icon' => '/assets/icons/slot-reef.svg',
            'rtp' => 96.0,
            'theme' => 'reef',
            'mechanic' => 'Центральные зоны',
            'type' => 'scatter',
            'win_type' => 'center',
            'cols' => 5,
            'rows' => 5,
            'symbols' => [
                'reef_shell',
                'reef_coral',
                'reef_star',
                'reef_pearl',
                'reef_wave',
                'reef_anchor',
                'reef_orb',
                'reef_scale',
            ],
            'symbol_labels' => [
                'reef_shell' => 'Ракушка',
                'reef_coral' => 'Коралл',
                'reef_star' => 'Морская звезда',
                'reef_pearl' => 'Жемчуг',
                'reef_wave' => 'Волна',
                'reef_anchor' => 'Якорь',
                'reef_orb' => 'Сфера',
                'reef_scale' => 'Чешуя',
            ],
            'weights' => [16, 14, 10, 8, 14, 10, 12, 16],
            'payouts' => [
                ['count' => 4, 'multiplier' => 0.8],
                ['count' => 6, 'multiplier' => 1.3],
                ['count' => 8, 'multiplier' => 1.9],
            ],
            'rare_symbols' => ['reef_pearl', 'reef_anchor'],
            'rare_bonus' => 0.5,
            'scatter' => 'reef_star',
        ],
        [
            'slug' => 'obsidian-rift',
            'name' => 'Obsidian Rift',
            'icon' => '/assets/icons/slot-rift.svg',
            'rtp' => 95.6,
            'theme' => 'rift',
            'mechanic' => 'Крестовые связки',
            'type' => 'cascade',
            'win_type' => 'cross',
            'cols' => 6,
            'rows' => 5,
            'symbols' => [
                'rift_shard',
                'rift_core',
                'rift_eye',
                'rift_flare',
                'rift_chain',
                'rift_claw',
                'rift_orb',
                'rift_spike',
            ],
            'symbol_labels' => [
                'rift_shard' => 'Осколок',
                'rift_core' => 'Ядро',
                'rift_eye' => 'Око',
                'rift_flare' => 'Вспышка',
                'rift_chain' => 'Цепь',
                'rift_claw' => 'Коготь',
                'rift_orb' => 'Сфера',
                'rift_spike' => 'Шип',
            ],
            'weights' => [18, 8, 10, 12, 12, 14, 12, 14],
            'payouts' => [
                ['count' => 5, 'multiplier' => 0.9],
                ['count' => 7, 'multiplier' => 1.4],
                ['count' => 9, 'multiplier' => 2.1],
            ],
            'rare_symbols' => ['rift_core', 'rift_eye'],
            'rare_bonus' => 0.6,
            'scatter' => 'rift_flare',
        ],
    ];
}

function slot_config(string $slug): array
{
    $slots = slots_catalog();
    foreach ($slots as $slot) {
        if ($slot['slug'] === $slug) {
            return $slot;
        }
    }
    return $slots[0];
}

function user_balance(int $userId): float
{
    $stmt = db()->prepare('SELECT balance FROM balances WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (float) $row['balance'] : 0.0;
}
