<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/admin.php';
admin_require_auth();

$todaySales = $pdo->query(
    "SELECT COALESCE(SUM(total), 0)
     FROM orders
     WHERE created_at >= CURRENT_DATE()
       AND created_at < CURRENT_DATE() + INTERVAL 1 DAY
       AND status IN ('paid', 'shipped', 'delivered')"
)->fetchColumn();

$statusRows = $pdo->query(
    'SELECT status, COUNT(*) AS order_count
     FROM orders
     GROUP BY status
     ORDER BY FIELD(status, "pending", "paid", "shipped", "delivered", "cancelled")'
)->fetchAll();

$topProducts = $pdo->query(
    'SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS quantity_sold
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status IN ("paid", "shipped", "delivered")
     GROUP BY oi.product_id, oi.product_name
     ORDER BY quantity_sold DESC, oi.product_name ASC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'Admin dashboard — Soko';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page">
    <div class="admin-heading">
        <div>
            <p class="eyebrow">Store administration</p>
            <h1>Dashboard</h1>
        </div>
        <div class="admin-actions">
            <a class="button" href="orders.php">Manage orders</a>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button class="text-button text-button-muted" type="submit">Sign out</button>
            </form>
        </div>
    </div>

    <div class="admin-metric">
        <span>Today's sales <small>paid orders</small></span>
        <strong><?= e(money($todaySales)) ?></strong>
    </div>

    <div class="admin-dashboard-grid">
        <section class="admin-panel">
            <h2>Orders by status</h2>
            <?php if ($statusRows === []): ?>
                <p class="admin-empty">No orders yet.</p>
            <?php else: ?>
                <ul class="status-list">
                    <?php foreach ($statusRows as $row): ?>
                        <li>
                            <span class="status-pill status-<?= e($row['status']) ?>"><?= e(ucfirst($row['status'])) ?></span>
                            <strong><?= (int) $row['order_count'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="admin-panel">
            <h2>Top 5 products</h2>
            <?php if ($topProducts === []): ?>
                <p class="admin-empty">No completed sales yet.</p>
            <?php else: ?>
                <ol class="top-products">
                    <?php foreach ($topProducts as $product): ?>
                        <li>
                            <span><?= e($product['product_name']) ?></span>
                            <strong><?= (int) $product['quantity_sold'] ?> sold</strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
