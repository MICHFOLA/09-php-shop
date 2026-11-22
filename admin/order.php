<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/admin.php';
admin_require_auth();

$orderId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($orderId === false) {
    http_response_code(404);
    $orderId = 0;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'Your session expired. Refresh this page and try again.';
    } else {
        $nextStatus = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
        $pdo->beginTransaction();
        try {
            $lockOrder = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? FOR UPDATE');
            $lockOrder->execute([$orderId]);
            $lockedOrder = $lockOrder->fetch();
            if (!$lockedOrder) {
                throw new DomainException('That order could not be found.');
            }

            $allowedStatuses = admin_status_transitions($lockedOrder['status']);
            if (!in_array($nextStatus, $allowedStatuses, true)) {
                throw new DomainException('That status change is not allowed.');
            }

            if ($nextStatus === 'cancelled') {
                $lockItems = $pdo->prepare(
                    'SELECT product_id, quantity
                     FROM order_items
                     WHERE order_id = ?
                     ORDER BY product_id
                     FOR UPDATE'
                );
                $lockItems->execute([$orderId]);
                $items = $lockItems->fetchAll();
                if ($items === []) {
                    throw new DomainException('This order has no items to restore.');
                }

                $restoreStock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                foreach ($items as $item) {
                    $restoreStock->execute([(int) $item['quantity'], (int) $item['product_id']]);
                    if ($restoreStock->rowCount() !== 1) {
                        throw new RuntimeException('An order product could not be updated.');
                    }
                }
            }

            $updateOrder = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND status = ?');
            $updateOrder->execute([$nextStatus, $orderId, $lockedOrder['status']]);
            if ($updateOrder->rowCount() !== 1) {
                throw new RuntimeException('The order status changed unexpectedly.');
            }

            $pdo->commit();
            set_flash('success', 'Order status updated to ' . $nextStatus . '.');
            redirect('order.php?id=' . $orderId);
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin order update failed: ' . $exception->getMessage());
            $error = 'The order could not be updated. No changes were saved; please try again.';
        }
    }
}

$order = null;
$items = [];
if ($orderId > 0) {
    $orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $orderStatement->execute(['id' => $orderId]);
    $order = $orderStatement->fetch();

    if ($order) {
        $itemsStatement = $pdo->prepare(
            'SELECT product_id, product_name, unit_price, quantity
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY product_name'
        );
        $itemsStatement->execute(['order_id' => $orderId]);
        $items = $itemsStatement->fetchAll();
    } else {
        http_response_code(404);
    }
}

$pageTitle = $order ? 'Order ' . $order['reference'] . ' — Soko admin' : 'Order not found — Soko admin';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page">
    <?php if (!$order): ?>
        <div class="empty-state"><h3>Order not found</h3><a class="button" href="orders.php">Back to orders</a></div>
    <?php else: ?>
        <div class="admin-heading">
            <div>
                <p class="eyebrow">Order details</p>
                <h1><?= e($order['reference']) ?></h1>
            </div>
            <a class="text-link" href="orders.php">Back to orders</a>
        </div>

        <?php if ($error !== ''): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>

        <div class="order-detail-grid">
            <section class="admin-panel">
                <h2>Customer</h2>
                <dl class="order-customer">
                    <dt>Name</dt><dd><?= e($order['customer_name']) ?></dd>
                    <dt>Email</dt><dd><a class="text-link" href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
                    <dt>Phone</dt><dd><?= e($order['phone']) ?></dd>
                    <dt>Delivery address</dt><dd><?= nl2br(e($order['address'])) ?></dd>
                    <dt>Placed</dt><dd><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></dd>
                </dl>
            </section>

            <section class="admin-panel">
                <h2>Items</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td data-label="Product"><?= e($item['product_name']) ?></td>
                                    <td data-label="Price"><?= e(money($item['unit_price'])) ?></td>
                                    <td data-label="Qty"><?= (int) $item['quantity'] ?></td>
                                    <td data-label="Total"><?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3">Order total</th><th><?= e(money($order['total'])) ?></th></tr></tfoot>
                    </table>
                </div>
            </section>
        </div>

        <section class="admin-panel status-update-panel">
            <div>
                <h2>Order status</h2>
                <span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
            </div>
            <?php $transitions = admin_status_transitions($order['status']); ?>
            <?php if ($transitions !== []): ?>
                <form class="status-change-form" method="post" action="order.php?id=<?= (int) $order['id'] ?>">
                    <?= csrf_field() ?>
                    <label class="sr-only" for="status">Change status</label>
                    <select id="status" name="status" required>
                        <?php foreach ($transitions as $transition): ?>
                            <option value="<?= e($transition) ?>"><?= e(ucfirst($transition)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button" type="submit">Update status</button>
                </form>
            <?php else: ?>
                <p class="admin-empty">This order is closed and can no longer be changed.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
