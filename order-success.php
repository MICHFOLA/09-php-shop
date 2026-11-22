<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = isset($_GET['ref']) && is_string($_GET['ref']) ? trim($_GET['ref']) : '';
$statement = $pdo->prepare('SELECT reference FROM orders WHERE reference = :reference LIMIT 1');
$statement->execute(['reference' => $reference]);
$order = $statement->fetch();

if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? 'Order received — Soko' : 'Order not found — Soko';
require __DIR__ . '/partials/header.php';
?>
<section class="order-success">
    <?php if ($order): ?>
        <p class="eyebrow">Thank you</p>
        <h1>Your order is in good hands.</h1>
        <p>We've received your order and will be in touch with delivery updates.</p>
        <p class="order-reference">Order reference <strong><?= e($order['reference']) ?></strong></p>
        <a class="button" href="index.php">Continue shopping</a>
    <?php else: ?>
        <p class="eyebrow">Order update</p>
        <h1>We couldn't find that order.</h1>
        <p>Check your order link or return to the shop.</p>
        <a class="button" href="index.php">Back to the shop</a>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
