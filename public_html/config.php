<?php
return [
    // Основные параметры сайта
    'site' => [
        'name' => 'Kasino Lux',
        'base_url' => '',
        'default_language' => 'ru',
        'support_email' => 'support@example.com',
    ],
    // Подключение к базе данных MySQL
    'db' => [
        'host' => 'localhost',
        'name' => 'kasino',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // Настройки безопасности и лимитов
    'security' => [
        'session_name' => 'kasino_session',
        'csrf_key' => 'change_this_secret_key',
        'rate_limit' => [
            'login' => ['window' => 300, 'max' => 10],
            'spin' => ['window' => 60, 'max' => 120],
            'withdrawal' => ['window' => 3600, 'max' => 5],
        ],
    ],
    // Параметры СБП (Тинькофф)
    'payments' => [
        'sbp' => [
            'merchant_id' => 'SBP_MERCHANT_ID',
            'secret' => 'SBP_SECRET',
            'min_amount' => 100,
            'max_amount' => 100000,
        ],
    ],
    // Бонусные механики
    'bonuses' => [
        'daily_amount' => 50,
        'welcome_amount' => 500,
        'welcome_wager' => 20,
        'cashback_percent' => 5,
    ],
    // PWA параметры
    'pwa' => [
        'name' => 'Kasino Lux',
        'short_name' => 'Kasino',
        'theme_color' => '#0b0b14',
        'background_color' => '#0b0b14',
    ],
];
