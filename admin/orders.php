<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/admin.php';
admin_require_auth();

$validStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['from']) && is_string($_GET['from']) ? $_GET['from'] : '';
$dateTo = isset($_GET['to']) && is_string($_GET['to']) ? $_GET['to'] : '';
$filters = [];
$parameters = [];

if ($status !== '' && in_array($status, $validStatuses, true)) {
    $filters[] = 'o.status = :status';
    $parameters['status'] = $status;
} else {
    $status = '';
}
if ($dateFrom !== '' && admin_valid_date($dateFrom)) {
    $filters[] = 'o.created_at >= :date_from';
    $parameters['date_from'] = $dateFrom . ' 00:00:00';
} else {
    $dateFrom = '';
}
if ($dateTo !== '' && admin_valid_date($dateTo)) {
    $filters[] = 'o.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
    $parameters['date_to'] = $dateTo . ' 00:00:00';
} else {
    $dateTo = '';
}

$where = $filters === [] ? '' : 'WHERE ' . implode(' AND ', $filters);
$statement = $pdo->prepare(
    'SELECT o.id, o.reference, o.customer_name, o.total, o.status, o.created_at,
            COALESCE(SUM(oi.quantity), 0) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     ' . $where . '
     GROUP BY o.id, o.reference, o.customer_name, o.total, o.status, o.created_at
     ORDER BY o.created_at DESC, o.id DESC'
);
$statement->execute($parameters);
$orders = $statement->fetchAll();

$pageTitle = 'Manage orders — Soko';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page">
    <div class="admin-heading">
        <div>
            <p class="eyebrow">Store administration</p>
            <h1>Orders</h1>
        </div>
        <div class="admin-actions">
            <a class="text-link" href="index.php">Dashboard</a>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button class="text-button text-button-muted" type="submit">Sign out</button>
            </form>
        </div>
    </div>

    <form class="admin-filters" method="get" action="orders.php">
        <label>Status
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach ($validStatuses as $option): ?>
                    <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>From
            <input type="date" name="from" value="<?= e($dateFrom) ?>">
        </label>
        <label>To
            <input type="date" name="to" value="<?= e($dateTo) ?>">
        </label>
        <button class="button" type="submit">Apply filters</button>
    </form>

    <?php if ($orders === []): ?>
        <div class="empty-state"><h3>No matching orders</h3><p>Try changing your filters.</p></div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Reference</th><th>Customer</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Reference"><a class="text-link" href="order.php?id=<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></td>
                            <td data-label="Customer"><?= e($order['customer_name']) ?></td>
                            <td data-label="Date"><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></td>
                            <td data-label="Items"><?= (int) $order['item_count'] ?></td>
                            <td data-label="Total"><?= e(money($order['total'])) ?></td>
                            <td data-label="Status"><span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                            <td><a class="text-link" href="order.php?id=<?= (int) $order['id'] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
