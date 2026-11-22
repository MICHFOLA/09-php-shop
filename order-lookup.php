<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = '';
$email = '';
$error = '';
$order = null;
$items = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = isset($_POST['reference']) && is_string($_POST['reference'])
        ? strtoupper(trim($_POST['reference']))
        : '';
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';

    if (!csrf_is_valid()) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($reference === '' || strlen($reference) > 20 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Enter a valid order reference and email address.';
    } else {
        $statement = $pdo->prepare(
            'SELECT reference, status, total, created_at
             FROM orders
             WHERE reference = :reference AND LOWER(email) = LOWER(:email)
             LIMIT 1'
        );
        $statement->execute(['reference' => $reference, 'email' => $email]);
        $order = $statement->fetch();
        if ($order) {
            $itemStatement = $pdo->prepare(
                'SELECT product_name, unit_price, quantity
                 FROM order_items
                 WHERE order_id = (SELECT id FROM orders WHERE reference = :reference)
                 ORDER BY product_name'
            );
            $itemStatement->execute(['reference' => $reference]);
            $items = $itemStatement->fetchAll();
        } else {
            $error = 'We could not find an order matching those details.';
        }
    }
}

$pageTitle = 'Track your order — Soko';
require __DIR__ . '/partials/header.php';
?>
<section class="lookup-page">
    <div class="section-heading cart-heading">
        <div><p class="eyebrow">A little update</p><h1>Track your order</h1></div>
    </div>

    <form class="checkout-form lookup-form" method="post" action="order-lookup.php">
        <?= csrf_field() ?>
        <?php if ($error !== ''): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <div class="checkout-field">
            <label for="reference">Order reference</label>
            <input id="reference" name="reference" type="text" maxlength="20" value="<?= e($reference) ?>" placeholder="ORD-20261122-ABCD" required>
        </div>
        <div class="checkout-field">
            <label for="email">Email used for the order</label>
            <input id="email" name="email" type="email" maxlength="160" value="<?= e($email) ?>" autocomplete="email" required>
        </div>
        <button class="button checkout-button" type="submit">Find my order</button>
    </form>

    <?php if ($order): ?>
        <section class="lookup-result" aria-live="polite">
            <div class="lookup-status">
                <div><p class="eyebrow">Order <?= e($order['reference']) ?></p><h2><?= e(ucfirst($order['status'])) ?></h2></div>
                <span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
            </div>
            <p class="lookup-date">Placed <?= e(date('M j, Y', strtotime($order['created_at']))) ?> · <?= e(money($order['total'])) ?></p>
            <?php if ($items !== []): ?>
                <ul class="checkout-items">
                    <?php foreach ($items as $item): ?>
                        <li><span><?= e($item['product_name']) ?> <span class="checkout-quantity">× <?= (int) $item['quantity'] ?></span></span><strong><?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
