<?php
require __DIR__ . '/auth.php';
function admin_header(string $title): void
{
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="/assets/css/style.css">';
    echo "<title>{$title}</title></head><body>";
    echo '<div class="topbar"><div class="logo">Kasino <span>Admin</span></div>';
    echo '<nav class="nav">';
    echo '<a href="/admin/dashboard.php">Дашборд</a>';
    echo '<a href="/admin/users.php">Пользователи</a>';
    echo '<a href="/admin/slots.php">Слоты</a>';
    echo '<a href="/admin/deposits.php">Депозиты</a>';
    echo '<a href="/admin/withdrawals.php">Выводы</a>';
    echo '<a href="/admin/antifraud.php">Антифрод</a>';
    echo '<a href="/admin/settings.php">Настройки</a>';
    echo '</nav></div>';
}

function admin_footer(): void
{
    echo '</body></html>';
}
