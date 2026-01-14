<?php
require __DIR__ . '/layout.php';
require_staff('deposits');
$rows = db()->query('SELECT p.id, u.email, p.amount, p.status, p.created_at FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 50')->fetchAll();
admin_header('Депозиты');
?>
<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <h2>Депозиты</h2>
            <p class="muted">Последние 50 транзакций по пополнению.</p>
        </div>
        <div class="admin-actions">
            <span class="admin-pill"><?php echo count($rows); ?> записей</span>
        </div>
    </div>
    <div class="admin-table card">
        <div class="admin-table-row admin-table-head">
            <div>ID</div>
            <div>Пользователь</div>
            <div>Сумма</div>
            <div>Статус</div>
            <div>Дата</div>
        </div>
        <?php foreach ($rows as $row): ?>
            <div class="admin-table-row">
                <div class="muted">#<?php echo $row['id']; ?></div>
                <div><?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?></div>
                <div><?php echo number_format((float) $row['amount'], 2, '.', ' '); ?>₽</div>
                <div>
                    <span class="admin-pill <?php echo $row['status'] === 'paid' ? 'is-success' : 'is-warning'; ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </div>
                <div class="muted"><?php echo $row['created_at']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php admin_footer(); ?>
