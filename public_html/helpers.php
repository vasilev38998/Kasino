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

function demo_mode_enabled(): bool
{
    $config = require __DIR__ . '/config.php';
    return !empty($config['site']['demo_mode']);
}

function require_login(bool $allowDemo = false): void
{
    if ($allowDemo && demo_mode_enabled()) {
        return;
    }
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function site_url(string $path = ''): string
{
    $config = require __DIR__ . '/config.php';
    $base = rtrim($config['site']['base_url'] ?? '', '/');
    if ($base !== '') {
        return $base . $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $path;
}

function format_email_address(string $email, ?string $name = null): string
{
    $email = trim($email);
    if ($name) {
        $safe = trim($name);
        return sprintf('"%s" <%s>', addcslashes($safe, '"\\'), $email);
    }
    return $email;
}

function smtp_send_message(array $smtp, string $to, string $subject, string $body): bool
{
    $host = $smtp['host'] ?? '';
    if ($host === '') {
        return false;
    }
    $port = (int) ($smtp['port'] ?? 587);
    $encryption = $smtp['encryption'] ?? 'tls';
    $remote = $encryption === 'ssl' ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
    $socket = stream_socket_client($remote, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }

    $read = function () use ($socket): string {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return $data;
    };
    $send = function (string $command) use ($socket): void {
        fwrite($socket, $command . "\r\n");
    };
    $expect = function (string $code) use ($read): bool {
        $response = $read();
        return str_starts_with($response, $code);
    };

    $read();
    $send('EHLO kasino.local');
    if (!$expect('250')) {
        fclose($socket);
        return false;
    }
    if ($encryption === 'tls') {
        $send('STARTTLS');
        if (!$expect('220')) {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        $send('EHLO kasino.local');
        if (!$expect('250')) {
            fclose($socket);
            return false;
        }
    }

    $username = (string) ($smtp['username'] ?? '');
    $password = (string) ($smtp['password'] ?? '');
    if ($username !== '' && $password !== '') {
        $send('AUTH LOGIN');
        if (!$expect('334')) {
            fclose($socket);
            return false;
        }
        $send(base64_encode($username));
        if (!$expect('334')) {
            fclose($socket);
            return false;
        }
        $send(base64_encode($password));
        if (!$expect('235')) {
            fclose($socket);
            return false;
        }
    }

    $fromEmail = $smtp['from_email'] ?? ($smtp['username'] ?? $to);
    $fromName = $smtp['from_name'] ?? null;
    $send('MAIL FROM:<' . $fromEmail . '>');
    if (!$expect('250')) {
        fclose($socket);
        return false;
    }
    $send('RCPT TO:<' . $to . '>');
    if (!$expect('250')) {
        fclose($socket);
        return false;
    }
    $send('DATA');
    if (!$expect('354')) {
        fclose($socket);
        return false;
    }

    $headers = [
        'From' => format_email_address($fromEmail, $fromName),
        'To' => $to,
        'Subject' => $subject,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
    ];
    $message = '';
    foreach ($headers as $key => $value) {
        $message .= $key . ': ' . $value . "\r\n";
    }
    $safeBody = str_replace(["\r\n", "\r"], "\n", $body);
    $safeBody = str_replace("\n", "\r\n", $safeBody);
    $message .= "\r\n" . $safeBody;
    $message = str_replace("\r\n.\r\n", "\r\n..\r\n", $message);
    $send($message . "\r\n.");
    $result = $expect('250');
    $send('QUIT');
    fclose($socket);
    return $result;
}

function send_email_message(string $to, string $subject, string $body): bool
{
    $config = require __DIR__ . '/config.php';
    $mail = $config['mail'] ?? [];
    if (!empty($mail['enabled'])) {
        return smtp_send_message($mail, $to, $subject, $body);
    }
    $from = $config['site']['support_email'] ?? 'support@example.com';
    $headers = [
        'From' => $from,
        'Content-Type' => 'text/plain; charset=UTF-8',
    ];
    $formatted = '';
    foreach ($headers as $key => $value) {
        $formatted .= $key . ': ' . $value . "\r\n";
    }
    return mail($to, $subject, $body, $formatted);
}

function generate_one_time_code(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function hash_one_time_code(string $code): string
{
    return hash('sha256', $code);
}

function issue_email_verification(int $userId, string $email): void
{
    $code = generate_one_time_code();
    $hash = hash_one_time_code($code);
    $expires = date('Y-m-d H:i:s', time() + 30 * 60);
    db()->prepare('UPDATE users SET email_verification_code = ?, email_verification_expires_at = ? WHERE id = ?')
        ->execute([$hash, $expires, $userId]);
    $subject = 'Подтверждение email';
    $body = "Ваш код подтверждения: {$code}\nКод действует 30 минут.";
    send_email_message($email, $subject, $body);
}

function issue_password_reset(int $userId, string $email): void
{
    $code = generate_one_time_code();
    $hash = hash_one_time_code($code);
    $expires = date('Y-m-d H:i:s', time() + 20 * 60);
    db()->prepare('UPDATE users SET password_reset_code = ?, password_reset_expires_at = ? WHERE id = ?')
        ->execute([$hash, $expires, $userId]);
    $subject = 'Восстановление пароля';
    $body = "Ваш код для восстановления пароля: {$code}\nКод действует 20 минут.";
    send_email_message($email, $subject, $body);
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

function site_setting(string $name, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($name, $cache)) {
        return $cache[$name];
    }
    $stmt = db()->prepare('SELECT value FROM settings WHERE name = ?');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    $value = $row ? (string) $row['value'] : $default;
    $cache[$name] = $value;
    return $value;
}

function render_header(string $title): void
{
    $config = require __DIR__ . '/config.php';
    $language = lang();
    $authed = current_user() ? '1' : '0';
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
    echo "<body data-auth=\"{$authed}\">\n";
    echo "<div class=\"app\">\n";
    echo "<header class=\"topbar\">\n";
    echo "<div class=\"logo\">Kasino <span>Lux</span></div>\n";
    echo "<nav class=\"nav\">\n";
    echo "<a href=\"/index.php\">" . t('nav_home') . "</a>\n";
    echo "<a href=\"/slots.php\">" . t('nav_slots') . "</a>\n";
    echo "<a href=\"/cases.php\">" . t('nav_cases') . "</a>\n";
    echo "<a href=\"/promotions.php\">" . t('nav_promotions') . "</a>\n";
    echo "<a href=\"/leaderboard.php\">" . t('nav_leaderboard') . "</a>\n";
    echo "<a href=\"/missions.php\">" . t('nav_missions') . "</a>\n";
    if (!current_user()) {
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
            'volatility' => 'high',
            'feature_name' => 'Каскадное мерцание',
            'feature_desc' => '3+ scatter запускают каскадный буст и добавляют +1.1x к множителю.',
            'bonus_hint' => 'Бонус: 3+ scatter',
            'feature_tag' => 'aurora_cascade',
            'feature_trigger' => 'scatter',
            'feature_threshold' => 3,
            'feature_bonus_min' => 1.0,
            'feature_bonus_max' => 1.4,
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
            'volatility' => 'medium',
            'feature_name' => 'Космические цепи',
            'feature_desc' => 'Комбо считаются по диагоналям и усиливают стабильные выигрыши.',
            'bonus_hint' => 'Комбо по диагонали',
            'feature_tag' => 'cosmic_chain',
            'feature_trigger' => 'best_count',
            'feature_threshold' => 7,
            'feature_bonus_min' => 0.6,
            'feature_bonus_max' => 1.0,
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
            'volatility' => 'high',
            'feature_name' => 'Липкие дикари',
            'feature_desc' => '2+ scatter активируют sticky-wilds и добавляют +0.7x к выплате.',
            'bonus_hint' => 'Бонус: 2+ scatter',
            'feature_tag' => 'dragon_sticky',
            'feature_trigger' => 'scatter',
            'feature_threshold' => 2,
            'feature_bonus_min' => 0.6,
            'feature_bonus_max' => 1.0,
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
            'volatility' => 'medium',
            'feature_name' => 'Штормовой множитель',
            'feature_desc' => '3+ scatter дают случайный буст от x0.8 до x1.8.',
            'bonus_hint' => 'Бонус: 3+ scatter',
            'feature_tag' => 'sky_storm',
            'feature_trigger' => 'scatter',
            'feature_threshold' => 3,
            'feature_bonus_min' => 0.8,
            'feature_bonus_max' => 1.8,
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
            'volatility' => 'medium',
            'feature_name' => 'Сладкий взрыв',
            'feature_desc' => 'Кластеры 12+ активируют burst и добавляют +0.8x.',
            'bonus_hint' => 'Бонус: кластер 12+',
            'feature_tag' => 'sugar_burst',
            'feature_trigger' => 'best_count',
            'feature_threshold' => 12,
            'feature_bonus_min' => 0.7,
            'feature_bonus_max' => 1.1,
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
            'volatility' => 'high',
            'feature_name' => 'Гем-шторм',
            'feature_desc' => '3+ scatter запускают gem-storm и добавляют +1.0x.',
            'bonus_hint' => 'Бонус: 3+ scatter',
            'feature_tag' => 'zenith_storm',
            'feature_trigger' => 'scatter',
            'feature_threshold' => 3,
            'feature_bonus_min' => 1.0,
            'feature_bonus_max' => 1.5,
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
            'volatility' => 'medium',
            'feature_name' => 'Орбитальный бонус',
            'feature_desc' => '3+ scatter активируют orbit-bonus и добавляют +0.9x.',
            'bonus_hint' => 'Бонус: 3+ scatter',
            'feature_tag' => 'orbit_bonus',
            'feature_trigger' => 'scatter',
            'feature_threshold' => 3,
            'feature_bonus_min' => 0.7,
            'feature_bonus_max' => 1.2,
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
            'volatility' => 'medium',
            'feature_name' => 'Рифовый множитель',
            'feature_desc' => 'Центральные серии 6+ активируют рифовый буст +0.5x–1.3x.',
            'bonus_hint' => 'Бонус: центр 6+',
            'feature_tag' => 'reef_bloom',
            'feature_trigger' => 'best_count',
            'feature_threshold' => 6,
            'feature_bonus_min' => 0.5,
            'feature_bonus_max' => 1.3,
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
            'volatility' => 'high',
            'feature_name' => 'Обсидиановый разлом',
            'feature_desc' => 'Крестовые серии 7+ активируют разлом и дают +1.1x–1.6x.',
            'bonus_hint' => 'Бонус: крест 7+',
            'feature_tag' => 'rift_break',
            'feature_trigger' => 'best_count',
            'feature_threshold' => 7,
            'feature_bonus_min' => 1.1,
            'feature_bonus_max' => 1.6,
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
        [
            'slug' => 'ember-eclipse',
            'name' => 'Ember Eclipse',
            'icon' => '/assets/icons/slot-ember.svg',
            'rtp' => 96.1,
            'theme' => 'ember',
            'mechanic' => 'Огненный коридор',
            'volatility' => 'high',
            'feature_name' => 'Пепельное кольцо',
            'feature_desc' => 'Серия 6+ по колоннам активирует кольцо и даёт +1.0x–1.7x.',
            'bonus_hint' => 'Бонус: колонны 6+',
            'feature_tag' => 'ember_ring',
            'feature_trigger' => 'best_count',
            'feature_threshold' => 6,
            'feature_bonus_min' => 1.0,
            'feature_bonus_max' => 1.7,
            'type' => 'burst',
            'win_type' => 'column',
            'cols' => 5,
            'rows' => 4,
            'symbols' => [
                'ember_core',
                'ember_flare',
                'ember_blade',
                'ember_ash',
                'ember_orb',
                'ember_spike',
                'ember_chain',
                'ember_crown',
            ],
            'symbol_labels' => [
                'ember_core' => 'Ядро',
                'ember_flare' => 'Вспышка',
                'ember_blade' => 'Клинок',
                'ember_ash' => 'Пепел',
                'ember_orb' => 'Сфера',
                'ember_spike' => 'Шип',
                'ember_chain' => 'Цепь',
                'ember_crown' => 'Корона',
            ],
            'weights' => [16, 12, 12, 14, 14, 10, 10, 6],
            'payouts' => [
                ['count' => 4, 'multiplier' => 0.6],
                ['count' => 6, 'multiplier' => 1.2],
                ['count' => 8, 'multiplier' => 2.0],
            ],
            'rare_symbols' => ['ember_crown', 'ember_core'],
            'rare_bonus' => 0.5,
            'scatter' => 'ember_flare',
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

function mission_period_key(string $period): string
{
    return match ($period) {
        'daily' => date('Y-m-d'),
        'weekly' => date('o-W'),
        default => 'all',
    };
}

function active_missions(): array
{
    $stmt = db()->query("SELECT * FROM missions WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY id DESC");
    return $stmt->fetchAll();
}

function mission_progress_map(int $userId, array $missionIds): array
{
    if (!$missionIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($missionIds), '?'));
    $stmt = db()->prepare("SELECT * FROM user_missions WHERE user_id = ? AND mission_id IN ({$placeholders})");
    $stmt->execute(array_merge([$userId], $missionIds));
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[(int) $row['mission_id']] = $row;
    }
    return $map;
}

function normalize_mission_progress(array $mission, ?array $row): array
{
    if (!$row) {
        return ['progress' => 0, 'completed_at' => null, 'claimed_at' => null, 'period_key' => mission_period_key($mission['period'])];
    }
    if ($mission['period'] !== 'once') {
        $currentKey = mission_period_key($mission['period']);
        if ($row['period_key'] !== $currentKey) {
            return ['progress' => 0, 'completed_at' => null, 'claimed_at' => null, 'period_key' => $currentKey];
        }
    }
    return $row;
}

function update_mission_progress(int $userId, string $type, float $amount): void
{
    if ($amount <= 0) {
        return;
    }
    $stmt = db()->prepare("SELECT * FROM missions WHERE is_active = 1 AND type = ? AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())");
    $stmt->execute([$type]);
    $missions = $stmt->fetchAll();
    if (!$missions) {
        return;
    }
    foreach ($missions as $mission) {
        $periodKey = mission_period_key($mission['period']);
        $progressStmt = db()->prepare('SELECT * FROM user_missions WHERE user_id = ? AND mission_id = ?');
        $progressStmt->execute([$userId, $mission['id']]);
        $row = $progressStmt->fetch();
        $progress = $row ? (float) $row['progress'] : 0.0;
        $completedAt = $row['completed_at'] ?? null;
        $claimedAt = $row['claimed_at'] ?? null;
        if ($row && $mission['period'] !== 'once' && $row['period_key'] !== $periodKey) {
            $progress = 0.0;
            $completedAt = null;
            $claimedAt = null;
        }
        $progress += $amount;
        if ($progress >= (float) $mission['target_value']) {
            if (!$completedAt) {
                $completedAt = date('Y-m-d H:i:s');
            }
        }
        if ($row) {
            db()->prepare('UPDATE user_missions SET progress = ?, period_key = ?, completed_at = ?, claimed_at = ? WHERE id = ?')
                ->execute([$progress, $periodKey, $completedAt, $claimedAt, $row['id']]);
        } else {
            db()->prepare('INSERT INTO user_missions (user_id, mission_id, progress, period_key, completed_at, claimed_at) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$userId, $mission['id'], $progress, $periodKey, $completedAt, $claimedAt]);
        }
    }
}

function claim_mission_reward(int $userId, int $missionId): array
{
    $stmt = db()->prepare('SELECT missions.*, user_missions.id AS progress_id, user_missions.progress, user_missions.completed_at, user_missions.claimed_at, user_missions.period_key FROM missions LEFT JOIN user_missions ON missions.id = user_missions.mission_id AND user_missions.user_id = ? WHERE missions.id = ?');
    $stmt->execute([$userId, $missionId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'message' => 'Миссия не найдена.'];
    }
    $normalized = normalize_mission_progress($row, $row['progress_id'] ? $row : null);
    if (!$normalized['completed_at'] || (float) $normalized['progress'] < (float) $row['target_value']) {
        return ['ok' => false, 'message' => 'Миссия ещё не выполнена.'];
    }
    if ($normalized['claimed_at']) {
        return ['ok' => false, 'message' => 'Награда уже получена.'];
    }
    $reward = (float) $row['reward_amount'];
    db()->prepare('UPDATE balances SET balance = balance + ? WHERE user_id = ?')
        ->execute([$reward, $userId]);
    if ($row['progress_id']) {
        db()->prepare('UPDATE user_missions SET claimed_at = ?, period_key = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s'), mission_period_key($row['period']), $row['progress_id']]);
    } else {
        db()->prepare('INSERT INTO user_missions (user_id, mission_id, progress, period_key, completed_at, claimed_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$userId, $missionId, $row['target_value'], mission_period_key($row['period']), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    }
    return ['ok' => true, 'message' => 'Награда начислена.'];
}

function active_tournaments(): array
{
    $stmt = db()->query("SELECT * FROM tournaments WHERE is_active = 1 ORDER BY starts_at DESC");
    return $stmt->fetchAll();
}

function tournament_entry(int $userId, int $tournamentId): ?array
{
    $stmt = db()->prepare('SELECT * FROM tournament_entries WHERE user_id = ? AND tournament_id = ?');
    $stmt->execute([$userId, $tournamentId]);
    return $stmt->fetch() ?: null;
}

function update_tournament_progress(int $userId, string $gameType, float $bet, float $win): void
{
    $stmt = db()->prepare("SELECT * FROM tournaments WHERE is_active = 1 AND starts_at <= NOW() AND ends_at >= NOW() AND (game_type = ? OR game_type = 'any')");
    $stmt->execute([$gameType]);
    $tournaments = $stmt->fetchAll();
    if (!$tournaments) {
        return;
    }
    foreach ($tournaments as $tournament) {
        $entry = tournament_entry($userId, (int) $tournament['id']);
        if (!$entry && (float) $tournament['entry_fee'] > 0) {
            continue;
        }
        if (!$entry) {
            db()->prepare('INSERT INTO tournament_entries (tournament_id, user_id, points, best_win, spins) VALUES (?, ?, 0, 0, 0)')
                ->execute([$tournament['id'], $userId]);
            $entry = tournament_entry($userId, (int) $tournament['id']);
        }
        $pointsAdd = 0.0;
        if ($tournament['metric'] === 'spins') {
            $pointsAdd = 1.0;
        } elseif ($tournament['metric'] === 'bet') {
            $pointsAdd = $bet;
        } elseif ($tournament['metric'] === 'win') {
            $pointsAdd = $win;
        }
        $newPoints = (float) $entry['points'] + $pointsAdd;
        $bestWin = max((float) $entry['best_win'], $win);
        $spins = (int) $entry['spins'] + 1;
        db()->prepare('UPDATE tournament_entries SET points = ?, best_win = ?, spins = ? WHERE id = ?')
            ->execute([$newPoints, $bestWin, $spins, $entry['id']]);
    }
}

function tournament_leaderboard(int $tournamentId, int $limit = 5): array
{
    $stmt = db()->prepare('SELECT tournament_entries.*, users.nickname FROM tournament_entries JOIN users ON users.id = tournament_entries.user_id WHERE tournament_id = ? ORDER BY points DESC, best_win DESC, spins DESC LIMIT ?');
    $stmt->execute([$tournamentId, $limit]);
    return $stmt->fetchAll();
}

function tournament_rank(int $tournamentId, int $userId): ?int
{
    $stmt = db()->prepare('SELECT user_id, points, best_win, spins FROM tournament_entries WHERE tournament_id = ? ORDER BY points DESC, best_win DESC, spins DESC');
    $stmt->execute([$tournamentId]);
    $rank = 0;
    while ($row = $stmt->fetch()) {
        $rank++;
        if ((int) $row['user_id'] === $userId) {
            return $rank;
        }
    }
    return null;
}

function tournament_prize_for_rank(float $pool, int $rank): float
{
    return match ($rank) {
        1 => $pool * 0.5,
        2 => $pool * 0.3,
        3 => $pool * 0.2,
        default => 0.0,
    };
}

function claim_tournament_reward(int $userId, int $tournamentId): array
{
    $entry = tournament_entry($userId, $tournamentId);
    if (!$entry) {
        return ['ok' => false, 'message' => 'Вы не участвуете в турнире.'];
    }
    if ($entry['reward_claimed_at']) {
        return ['ok' => false, 'message' => 'Награда уже получена.'];
    }
    $stmt = db()->prepare('SELECT * FROM tournaments WHERE id = ?');
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();
    if (!$tournament) {
        return ['ok' => false, 'message' => 'Турнир не найден.'];
    }
    if (strtotime($tournament['ends_at']) > time()) {
        return ['ok' => false, 'message' => 'Турнир ещё не завершён.'];
    }
    $rank = tournament_rank($tournamentId, $userId);
    if (!$rank || $rank > 3) {
        return ['ok' => false, 'message' => 'Вы не в призовой зоне.'];
    }
    $reward = round(tournament_prize_for_rank((float) $tournament['prize_pool'], $rank), 2);
    if ($reward <= 0) {
        return ['ok' => false, 'message' => 'Награда недоступна.'];
    }
    db()->prepare('UPDATE balances SET balance = balance + ? WHERE user_id = ?')
        ->execute([$reward, $userId]);
    db()->prepare('UPDATE tournament_entries SET reward_amount = ?, reward_claimed_at = ? WHERE id = ?')
        ->execute([$reward, date('Y-m-d H:i:s'), $entry['id']]);
    return ['ok' => true, 'message' => 'Награда начислена.'];
}
