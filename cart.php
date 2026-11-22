<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        set_flash('error', 'Your session expired. Please try that bag update again.');
        redirect('cart.php');
    }

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Your bag is now empty.');
    } else {
        $productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($productId === false) {
            set_flash('error', 'That product could not be found in your bag.');
        } elseif (!array_key_exists($productId, cart_data())) {
            set_flash('error', 'That item is no longer in your bag.');
        } elseif ($action === 'remove') {
            cart_remove($productId);
            set_flash('success', 'The item was removed from your bag.');
        } elseif ($action === 'update') {
            $quantity = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($quantity === false) {
                set_flash('error', 'Enter a quantity of at least one.');
            } else {
                $adjusted = cart_update($productId, $quantity);
                set_flash(
                    'success',
                    $adjusted
                        ? 'The quantity was adjusted to current availability.'
                        : 'Your bag was updated.'
                );
            }
        } else {
            set_flash('error', 'That bag action is not available.');
        }
    }

    redirect('cart.php');
}

$items = cart_items();
$total = array_sum(array_column($items, 'line_total'));
$pageTitle = 'Your bag — Soko';
require __DIR__ . '/partials/header.php';
?>
<section class="cart-page" aria-labelledby="cart-heading">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">Almost yours</p>
            <h1 id="cart-heading">Your bag</h1>
        </div>
        <a class="text-link" href="index.php#products">Continue shopping</a>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state cart-empty">
            <h2>Your bag is taking a little break</h2>
            <p>Find something thoughtful for your everyday.</p>
            <a class="button" href="index.php#products">Explore the collection</a>
        </div>
    <?php else: ?>
        <div class="cart-table-wrap">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Price</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Total</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-product">
                                    <a class="cart-product-image" href="product.php?slug=<?= urlencode($item['slug']) ?>">
                                        <img src="<?= e($item['image_url'] ?? '') ?>" alt="">
                                    </a>
                                    <a class="cart-product-name" href="product.php?slug=<?= urlencode($item['slug']) ?>"><?= e($item['name']) ?></a>
                                </div>
                            </td>
                            <td data-label="Price"><?= e(money($item['price'])) ?></td>
                            <td data-label="Quantity">
                                <form class="cart-item-form" method="post" action="cart.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                    <input class="cart-quantity" type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="<?= (int) $item['stock'] ?>" aria-label="Quantity of <?= e($item['name']) ?>">
                                    <button class="text-button" type="submit" name="action" value="update">Update</button>
                                </form>
                            </td>
                            <td data-label="Total" class="cart-line-total"><?= e(money($item['line_total'])) ?></td>
                            <td class="cart-remove-cell">
                                <form method="post" action="cart.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                    <button class="text-button text-button-muted" type="submit" name="action" value="remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-bottom">
            <form method="post" action="cart.php">
                <?= csrf_field() ?>
                <button class="text-button text-button-muted" type="submit" name="action" value="clear">Clear bag</button>
            </form>
            <aside class="cart-summary">
                <div class="subtotal-row">
                    <span>Subtotal</span>
                    <strong><?= e(money($total)) ?></strong>
                </div>
                <p class="summary-note">Shipping and any applicable fees are calculated at checkout.</p>
                <a class="button checkout-button" href="checkout.php">Proceed to checkout</a>
            </aside>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
