<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This seeder can only be run from the command line.\n");
}

require __DIR__ . '/app/bootstrap.php';

$categoryRows = [
    ['Home & Living', 'home-living'],
    ['Accessories', 'accessories'],
    ['Wellness', 'wellness'],
    ['Kitchen', 'kitchen'],
    ['Stationery', 'stationery'],
];

$products = [
    ['home-living', 'Linen Cushion Cover', 'linen-cushion-cover', 'A soft, breathable linen cushion cover in a warm natural tone. A simple way to bring texture to your sofa or bed.', 18500, 18],
    ['home-living', 'Handwoven Storage Basket', 'handwoven-storage-basket', 'A sturdy handwoven basket for blankets, toys, or all the little things that need a home.', 24000, 12],
    ['home-living', 'Ceramic Table Vase', 'ceramic-table-vase', 'A sculptural stoneware vase with a softly speckled glaze. Lovely with fresh stems or on its own.', 15500, 9],
    ['home-living', 'Cotton Throw Blanket', 'cotton-throw-blanket', 'A lightweight cotton throw with a relaxed weave, made for slow mornings and cool evenings.', 32500, 7],
    ['accessories', 'Everyday Canvas Tote', 'everyday-canvas-tote', 'A roomy, durable cotton canvas tote with comfortable straps for errands and everyday carry.', 12000, 25],
    ['accessories', 'Leather Card Holder', 'leather-card-holder', 'A slim card holder made from full-grain leather that softens beautifully with use.', 19500, 14],
    ['accessories', 'Minimalist Wristwatch', 'minimalist-wristwatch', 'A clean, easy-to-wear watch with a quiet dial and an adjustable genuine leather strap.', 58000, 6],
    ['wellness', 'Botanical Hand Wash', 'botanical-hand-wash', 'A gentle plant-based hand wash with a fresh botanical scent and a nourishing finish.', 8500, 20],
    ['wellness', 'Scented Soy Candle', 'scented-soy-candle', 'A slow-burning soy candle with notes of cedar, citrus, and soft spice.', 14500, 15],
    ['wellness', 'Reusable Water Bottle', 'reusable-water-bottle', 'A double-wall insulated stainless steel bottle that keeps drinks cold or warm on the go.', 18000, 11],
    ['kitchen', 'Stoneware Coffee Mug', 'stoneware-coffee-mug', 'A comfortable, handcrafted stoneware mug with a generous handle and satin glaze.', 9500, 30],
    ['kitchen', 'Acacia Serving Board', 'acacia-serving-board', 'A versatile acacia wood serving board for breakfast, snacks, or a relaxed shared table.', 22000, 8],
    ['kitchen', 'Glass Oil Dispenser', 'glass-oil-dispenser', 'A clear glass dispenser with a controlled pour spout to keep your cooking essentials close.', 11000, 0],
    ['stationery', 'Undated Daily Planner', 'undated-daily-planner', 'An undated planner with space for priorities, notes, and small wins, ready whenever you are.', 13500, 17],
    ['stationery', 'Hardcover Dot Journal', 'hardcover-dot-journal', 'A lay-flat, hardcover dot journal for sketches, lists, and everything in between.', 10500, 22],
];

$pdo->beginTransaction();

try {
    $categoryInsert = $pdo->prepare(
        'INSERT INTO categories (name, slug)
         VALUES (:name, :slug)
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    );
    foreach ($categoryRows as [$name, $slug]) {
        $categoryInsert->execute(['name' => $name, 'slug' => $slug]);
    }

    $categoryIds = [];
    $categorySelect = $pdo->query('SELECT id, slug FROM categories');
    foreach ($categorySelect as $category) {
        $categoryIds[$category['slug']] = (int) $category['id'];
    }

    $productInsert = $pdo->prepare(
        'INSERT INTO products
            (category_id, name, slug, description, price, stock, image_url, is_active)
         VALUES
            (:category_id, :name, :slug, :description, :price, :stock, :image_url, 1)
         ON DUPLICATE KEY UPDATE
            category_id = VALUES(category_id),
            name = VALUES(name),
            description = VALUES(description),
            price = VALUES(price),
            stock = VALUES(stock),
            image_url = VALUES(image_url),
            is_active = 1'
    );

    foreach ($products as [$categorySlug, $name, $slug, $description, $price, $stock]) {
        $productInsert->execute([
            'category_id' => $categoryIds[$categorySlug],
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => 'https://placehold.co/600x400?text=' . rawurlencode($name),
        ]);
    }

    $pdo->commit();
    fwrite(STDOUT, 'Seeded ' . count($products) . ' products across ' . count($categoryRows) . " categories.\n");
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
