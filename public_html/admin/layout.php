<?php
require __DIR__ . '/auth.php';

function admin_nav_link(string $href, string $label): void
{
    $current = basename($_SERVER['PHP_SELF'] ?? '');
    $target = basename($href);
    $active = $current === $target ? ' is-active' : '';
    echo "<a class=\"admin-link{$active}\" href=\"{$href}\">{$label}</a>";
}

function admin_header(string $title): void
{
    $staff = staff_user();
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="/assets/css/style.css">';
    echo "<title>{$title}</title></head><body class=\"admin-body\">";
    echo '<div class="admin-shell">';
    echo '<aside class="admin-sidebar">';
    echo '<div class="admin-brand">Kasino <span>Admin</span></div>';
    echo '<div class="admin-nav-group"><div class="admin-nav-title">Обзор</div>';
    admin_nav_link('/admin/dashboard.php', 'Дашборд');
    admin_nav_link('/admin/audit_log.php', 'Журнал');
    echo '</div>';
    echo '<div class="admin-nav-group"><div class="admin-nav-title">Пользователи</div>';
    admin_nav_link('/admin/users.php', 'Пользователи');
    admin_nav_link('/admin/balances.php', 'Балансы');
    admin_nav_link('/admin/bonuses.php', 'Бонусы');
    admin_nav_link('/admin/notifications.php', 'Уведомления');
    echo '</div>';
    echo '<div class="admin-nav-group"><div class="admin-nav-title">Контент</div>';
    admin_nav_link('/admin/slots.php', 'Слоты');
    admin_nav_link('/admin/cases.php', 'Кейсы');
    admin_nav_link('/admin/missions.php', 'Миссии');
    admin_nav_link('/admin/tournaments.php', 'Турниры');
    admin_nav_link('/admin/cms.php', 'CMS');
    admin_nav_link('/admin/promotions.php', 'Промо');
    echo '</div>';
    echo '<div class="admin-nav-group"><div class="admin-nav-title">Платежи</div>';
    admin_nav_link('/admin/deposits.php', 'Депозиты');
    admin_nav_link('/admin/withdrawals.php', 'Выводы');
    admin_nav_link('/admin/promo_codes.php', 'Промокоды');
    echo '</div>';
    echo '<div class="admin-nav-group"><div class="admin-nav-title">Безопасность</div>';
    admin_nav_link('/admin/antifraud.php', 'Антифрод');
    admin_nav_link('/admin/staff.php', 'Сотрудники');
    echo '</div>';
    echo '<div class="admin-nav-group"><div class="admin-nav-title">Служба</div>';
    admin_nav_link('/admin/support.php', 'Поддержка');
    admin_nav_link('/admin/settings.php', 'Настройки');
    echo '</div>';
    echo '</aside>';
    echo '<div class="admin-main">';
    echo '<div class="admin-topbar">';
    echo '<div>';
    echo "<div class=\"admin-title\">{$title}</div>";
    echo '<div class="admin-subtitle">Обновляйте данные быстро и аккуратно</div>';
    echo '</div>';
    echo '<div class="admin-topbar-actions">';
    echo '<form class="admin-search" method="get" action="#">';
    echo '<input type="search" name="q" placeholder="Поиск по панели" aria-label="Поиск по панели">';
    echo '<button class="btn ghost" type="submit">Найти</button>';
    echo '</form>';
    if ($staff) {
        $email = htmlspecialchars($staff['email'], ENT_QUOTES);
        echo "<div class=\"admin-user\">{$email}</div>";
    }
    echo '</div>';
    echo '</div>';
    echo '<main class="admin-content">';
}

function admin_footer(): void
{
    echo '</main></div></div></body></html>';
}
