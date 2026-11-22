<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$categories = $pdo->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
$categorySlug = isset($_GET['category']) && is_string($_GET['category'])
    ? trim($_GET['category'])
    : '';
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'newest';
$sortOptions = [
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$conditions = ['p.is_active = 1'];
$parameters = [];
if ($categorySlug !== '') {
    $conditions[] = 'c.slug = :category';
    $parameters['category'] = $categorySlug;
}
if ($search !== '') {
    $conditions[] = '(p.name LIKE :search_name OR p.description LIKE :search_description)';
    $parameters['search_name'] = '%' . $search . '%';
    $parameters['search_description'] = '%' . $search . '%';
}

$sql = 'SELECT p.id, p.name, p.slug, p.price, p.stock, p.image_url, c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY ' . $sortOptions[$sort];
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$products = $statement->fetchAll();

$pageTitle = 'Soko — thoughtful finds for everyday living';
require __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Good things, chosen well</p>
        <h1>Make room for the everyday.</h1>
        <p>Discover thoughtful home, kitchen, and personal essentials made to bring a little more joy to your routine.</p>
    </div>
</section>

<section id="products" aria-labelledby="products-heading">
    <div class="section-heading">
        <h2 id="products-heading">Shop the collection</h2>
        <p class="results-count"><?= count($products) ?> <?= count($products) === 1 ? 'find' : 'finds' ?> to explore</p>
    </div>

    <form class="toolbar" method="get" action="index.php">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products..." aria-label="Search products">
        <select name="category" aria-label="Filter by category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="sort" aria-label="Sort products">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
        </select>
        <button class="button" type="submit">Find products</button>
    </form>

    <?php if ($products === []): ?>
        <div class="empty-state">
            <h3>No finds this time</h3>
            <p>Try a different search or browse all of our collection.</p>
            <a class="button" href="index.php">View all products</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <a class="product-image-wrap" href="product.php?slug=<?= urlencode($product['slug']) ?>">
                        <img class="product-image" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php if ((int) $product['stock'] < 1): ?>
                            <span class="badge badge-out">Out of stock</span>
                        <?php endif; ?>
                    </a>
                    <div class="product-info">
                        <p class="product-category"><?= e($product['category_name']) ?></p>
                        <h3 class="product-name"><a href="product.php?slug=<?= urlencode($product['slug']) ?>"><?= e($product['name']) ?></a></h3>
                        <p class="product-price"><?= e(money($product['price'])) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
