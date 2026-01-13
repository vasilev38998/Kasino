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
            'mechanic' => 'Каскады + множители',
            'type' => 'cascade',
            'cols' => 6,
            'rows' => 5,
            'symbols' => ['A', 'K', 'Q', 'J', '10', '9', '★', '✦'],
            'scatter' => '★',
        ],
        [
            'slug' => 'cosmic-cluster',
            'name' => 'Cosmic Cluster',
            'icon' => '/assets/icons/slot-cosmic.svg',
            'rtp' => 95.7,
            'theme' => 'cosmic',
            'mechanic' => 'Variable Ways',
            'type' => 'ways',
            'cols' => 5,
            'rows' => 4,
            'symbols' => ['A', 'K', 'Q', 'J', '10', '9', '✶', '✹'],
            'scatter' => '✶',
        ],
        [
            'slug' => 'dragon-sticky',
            'name' => 'Dragon Sticky Wilds',
            'icon' => '/assets/icons/slot-dragon.svg',
            'rtp' => 94.9,
            'theme' => 'dragon',
            'mechanic' => 'Sticky Wilds',
            'type' => 'sticky',
            'cols' => 6,
            'rows' => 5,
            'symbols' => ['A', 'K', 'Q', 'J', '10', '9', '🐉', '🔥'],
            'scatter' => '🔥',
        ],
        [
            'slug' => 'sky-titans',
            'name' => 'Sky Titans',
            'icon' => '/assets/icons/slot-sky.svg',
            'rtp' => 96.4,
            'theme' => 'sky',
            'mechanic' => 'Scatter Multiplier',
            'type' => 'scatter',
            'cols' => 6,
            'rows' => 5,
            'symbols' => ['A', 'K', 'Q', 'J', '10', '9', '⚡', '☁'],
            'scatter' => '⚡',
        ],
        [
            'slug' => 'sugar-bloom',
            'name' => 'Sugar Bloom',
            'icon' => '/assets/icons/slot-sugar.svg',
            'rtp' => 96.1,
            'theme' => 'sugar',
            'mechanic' => 'Cluster Pays',
            'type' => 'cluster',
            'cols' => 7,
            'rows' => 7,
            'symbols' => ['🍬', '🍭', '🍫', '🍒', '🍋', '🍇', '⭐', '💎'],
            'scatter' => '⭐',
        ],
        [
            'slug' => 'zenith-gems',
            'name' => 'Zenith Gems',
            'icon' => '/assets/icons/slot-zenith.svg',
            'rtp' => 96.3,
            'theme' => 'zenith',
            'mechanic' => 'Gems Burst',
            'type' => 'burst',
            'cols' => 5,
            'rows' => 5,
            'symbols' => ['🔷', '🔶', '🔺', '🔸', '💎', '✨', 'A', 'K'],
            'scatter' => '✨',
        ],
        [
            'slug' => 'orbit-jewels',
            'name' => 'Orbit Jewels',
            'icon' => '/assets/icons/slot-orbit.svg',
            'rtp' => 95.9,
            'theme' => 'orbit',
            'mechanic' => 'Orbit Bonus',
            'type' => 'orbit',
            'cols' => 6,
            'rows' => 4,
            'symbols' => ['🪐', '🌙', '⭐', '💠', 'A', 'K', 'Q', 'J'],
            'scatter' => '⭐',
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
