<?php
declare(strict_types=1);

function cart_data(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $cart = [];
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $id = filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $qty = filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id !== false && $qty !== false) {
            $cart[$id] = $qty;
        }
    }

    $_SESSION['cart'] = $cart;

    return $cart;
}

function cart_product(int $id): ?array
{
    global $pdo;

    $statement = $pdo->prepare(
        'SELECT id, name, slug, price, stock, image_url, is_active
         FROM products
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $product = $statement->fetch();

    return $product ?: null;
}

function cart_add(int $id, int $qty): bool
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product and quantity must be positive integers.');
    }

    $product = cart_product($id);
    if ($product === null || (int) $product['is_active'] !== 1) {
        throw new DomainException('This product is no longer available.');
    }

    $cart = cart_data();
    $currentQuantity = $cart[$id] ?? 0;
    $stock = (int) $product['stock'];
    if ($stock < 1) {
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        return true;
    }

    $adjustedExistingQuantity = min($currentQuantity, $stock);
    $available = $stock - $adjustedExistingQuantity;
    $addedQuantity = min($qty, $available);
    $adjusted = $adjustedExistingQuantity !== $currentQuantity || $addedQuantity !== $qty;

    if ($adjustedExistingQuantity + $addedQuantity > 0) {
        $cart[$id] = $adjustedExistingQuantity + $addedQuantity;
    } else {
        unset($cart[$id]);
    }

    $_SESSION['cart'] = $cart;

    return $adjusted;
}

function cart_update(int $id, int $qty): bool
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product and quantity must be positive integers.');
    }

    $cart = cart_data();
    if (!array_key_exists($id, $cart)) {
        return false;
    }

    $product = cart_product($id);
    if ($product === null || (int) $product['is_active'] !== 1 || (int) $product['stock'] < 1) {
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        return true;
    }

    $stock = (int) $product['stock'];
    $cart[$id] = min($qty, $stock);
    $_SESSION['cart'] = $cart;

    return $qty > $stock;
}

function cart_remove(int $id): void
{
    $cart = cart_data();
    unset($cart[$id]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_items(): array
{
    global $pdo;

    $cart = cart_data();
    if ($cart === []) {
        return [];
    }

    $placeholders = [];
    $parameters = [];
    foreach (array_keys($cart) as $index => $id) {
        $placeholder = ':product_' . $index;
        $placeholders[] = $placeholder;
        $parameters[$placeholder] = $id;
    }

    $statement = $pdo->prepare(
        'SELECT id, name, slug, price, stock, image_url, is_active
         FROM products
         WHERE id IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($parameters);
    $products = [];
    foreach ($statement->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    $lines = [];
    $adjusted = false;
    foreach ($cart as $id => $quantity) {
        $product = $products[$id] ?? null;
        if ($product === null || (int) $product['is_active'] !== 1 || (int) $product['stock'] < 1) {
            unset($cart[$id]);
            $adjusted = true;
            continue;
        }

        $stock = (int) $product['stock'];
        if ($quantity > $stock) {
            $quantity = $stock;
            $cart[$id] = $quantity;
            $adjusted = true;
        }

        $unitPrice = (float) $product['price'];
        $lines[] = [
            'product_id' => $id,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'image_url' => $product['image_url'],
            'quantity' => $quantity,
            'price' => $unitPrice,
            'line_total' => round($unitPrice * $quantity, 2),
            'stock' => $stock,
            'stock_check' => $quantity <= $stock,
        ];
    }

    $_SESSION['cart'] = $cart;
    if ($adjusted) {
        set_flash('success', 'Your bag was updated to reflect current product availability.');
    }

    return $lines;
}

function cart_total(): float
{
    $total = 0.0;
    foreach (cart_items() as $item) {
        $total += $item['line_total'];
    }

    return $total;
}
