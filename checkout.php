<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/cart.php';

$values = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
];
$errors = [];
$checkoutError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $_) {
        $values[$field] = isset($_POST[$field]) && is_string($_POST[$field])
            ? trim($_POST[$field])
            : '';
    }

    if (!csrf_is_valid()) {
        $checkoutError = 'Your session expired. Please refresh the page and submit your order again.';
    }

    if ($values['name'] === '' || strlen($values['name']) > 120) {
        $errors['name'] = 'Enter your name (up to 120 characters).';
    }
    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || strlen($values['email']) > 160) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (
        strlen($values['phone']) < 7
        || strlen($values['phone']) > 20
        || preg_match('/^[0-9+().\-\s]+$/', $values['phone']) !== 1
        || preg_match_all('/[0-9]/', $values['phone']) < 7
    ) {
        $errors['phone'] = 'Enter a valid phone number, including its country code if needed.';
    }
    if ($values['address'] === '' || strlen($values['address']) > 5000) {
        $errors['address'] = 'Enter a delivery address (up to 5,000 characters).';
    }

    $cart = cart_data();
    if ($cart === []) {
        $checkoutError = 'Your bag is empty. Add an item before checking out.';
    }

    if ($checkoutError === '' && $errors === []) {
        $pdo->beginTransaction();

        try {
            $cartItems = [];
            $totalCents = 0;
            $lockProduct = $pdo->prepare(
                'SELECT id, name, price, stock, is_active
                 FROM products
                 WHERE id = ?
                 FOR UPDATE'
            );

            ksort($cart, SORT_NUMERIC);
            foreach ($cart as $productId => $quantity) {
                $lockProduct->execute([$productId]);
                $product = $lockProduct->fetch();
                if (
                    !$product
                    || (int) $product['is_active'] !== 1
                    || (int) $product['stock'] < $quantity
                ) {
                    throw new DomainException(
                        'One or more items in your bag are no longer available in the requested quantity. Please review your bag and try again.'
                    );
                }

                $unitPrice = (string) $product['price'];
                $unitPriceCents = (int) round((float) $unitPrice * 100);
                $totalCents += $unitPriceCents * $quantity;
                $cartItems[] = [
                    'product_id' => (int) $product['id'],
                    'product_name' => $product['name'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                ];
            }

            if ($cartItems === []) {
                throw new DomainException('Your bag is empty. Add an item before checking out.');
            }

            $total = number_format($totalCents / 100, 2, '.', '');
            $referenceAlphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $referenceSuffix = '';
            for ($index = 0; $index < 4; $index++) {
                $referenceSuffix .= $referenceAlphabet[random_int(0, strlen($referenceAlphabet) - 1)];
            }
            $reference = 'ORD-' . date('Ymd') . '-' . $referenceSuffix;

            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (reference, customer_name, email, phone, address, total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insertOrder->execute([
                $reference,
                $values['name'],
                $values['email'],
                $values['phone'],
                $values['address'],
                $total,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $updateStock = $pdo->prepare(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );

            foreach ($cartItems as $item) {
                $insertItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['unit_price'],
                    $item['quantity'],
                ]);
                $updateStock->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $item['quantity'],
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new DomainException(
                        'Stock changed while placing your order. Please review your bag and try again.'
                    );
                }
            }

            $pdo->commit();
            cart_clear();
            redirect('order-success.php?ref=' . urlencode($reference));
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $checkoutError = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Checkout failed: ' . $exception->getMessage());
            $checkoutError = 'We could not place your order just now. Your bag is unchanged; please try again.';
        }
    }
}

$items = cart_items();
if ($items === [] && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Your bag is empty. Add an item before checking out.');
    redirect('cart.php');
}
$total = array_sum(array_column($items, 'line_total'));
$pageTitle = 'Checkout — Soko';
require __DIR__ . '/partials/header.php';
?>
<section class="checkout-page" aria-labelledby="checkout-heading">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">One last step</p>
            <h1 id="checkout-heading">Checkout</h1>
        </div>
        <a class="text-link" href="cart.php">Back to your bag</a>
    </div>

    <?php if ($checkoutError !== ''): ?>
        <div class="flash flash-error" role="alert"><?= e($checkoutError) ?></div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="flash flash-error" role="alert">
            <p>Please check the details below:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($items !== []): ?>
        <div class="checkout-layout">
            <form class="checkout-form" method="post" action="checkout.php">
                <?= csrf_field() ?>
                <h2>Delivery details</h2>
                <div class="checkout-field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" maxlength="120" autocomplete="name" value="<?= e($values['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
                </div>
                <div class="checkout-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" maxlength="160" autocomplete="email" value="<?= e($values['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="checkout-field">
                    <label for="phone">Phone number</label>
                    <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($values['phone']) ?>" required>
                    <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                </div>
                <div class="checkout-field">
                    <label for="address">Delivery address</label>
                    <textarea id="address" name="address" rows="4" maxlength="5000" autocomplete="street-address" required><?= e($values['address']) ?></textarea>
                    <?php if (isset($errors['address'])): ?><span class="field-error"><?= e($errors['address']) ?></span><?php endif; ?>
                </div>
                <button class="button checkout-button" type="submit">Place order · <?= e(money($total)) ?></button>
            </form>

            <aside class="checkout-summary" aria-labelledby="summary-heading">
                <h2 id="summary-heading">Order summary</h2>
                <ul class="checkout-items">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <span><?= e($item['name']) ?> <span class="checkout-quantity">× <?= (int) $item['quantity'] ?></span></span>
                            <strong><?= e(money($item['line_total'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="subtotal-row checkout-total">
                    <span>Total</span>
                    <strong><?= e(money($total)) ?></strong>
                </div>
                <p class="summary-note">Your order total is calculated from current product prices.</p>
            </aside>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
