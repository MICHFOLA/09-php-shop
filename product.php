<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/cart.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$statement = $pdo->prepare(
    'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, c.name AS category_name
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.slug = :slug AND p.is_active = 1
     LIMIT 1'
);
$statement->execute(['slug' => $slug]);
$product = $statement->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found — Soko';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="empty-state">
        <h3>We couldn't find that product</h3>
        <p>It may have moved or is no longer available.</p>
        <a class="button" href="index.php">Back to the shop</a>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        set_flash('error', 'Your session expired. Please try adding the item again.');
        redirect('product.php?slug=' . urlencode($product['slug']));
    }

    $quantityInput = $_POST['quantity'] ?? '';
    $quantity = filter_var($quantityInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($quantity === false) {
        set_flash('error', 'Choose a valid quantity.');
    } else {
        try {
            $adjusted = cart_add((int) $product['id'], $quantity);
            set_flash(
                'success',
                $adjusted
                    ? 'Your bag was updated to the available stock quantity.'
                    : $product['name'] . ' added to your bag.'
            );
        } catch (DomainException $exception) {
            set_flash('error', $exception->getMessage());
        }
    }

    redirect('product.php?slug=' . urlencode($product['slug']));
}

$pageTitle = $product['name'] . ' — Soko';
require __DIR__ . '/partials/header.php';
?>
<section class="product-detail">
    <div class="detail-image-wrap">
        <img class="detail-image" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
    </div>
    <div class="product-copy">
        <p class="eyebrow"><?= e($product['category_name']) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <p class="detail-price"><?= e(money($product['price'])) ?></p>
        <p class="detail-description"><?= e($product['description']) ?></p>
        <?php if ((int) $product['stock'] > 0): ?>
            <p class="stock-note"><?= (int) $product['stock'] ?> in stock</p>
        <?php else: ?>
            <p class="stock-note out">Out of stock</p>
        <?php endif; ?>
        <form class="add-form" method="post" action="product.php?slug=<?= urlencode($product['slug']) ?>">
            <?= csrf_field() ?>
            <div class="quantity-field">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" max="<?= (int) $product['stock'] ?>" value="1" required <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>
            </div>
            <button class="button" type="submit" <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>Add to bag</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
