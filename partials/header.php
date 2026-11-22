<?php
$cartCount = array_sum(array_map('intval', $_SESSION['cart'] ?? []));
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f7f4">
    <title><?= e($pageTitle ?? 'Soko — thoughtful finds for everyday living') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php" aria-label="Soko home">soko<span>.</span></a>
        <p class="header-note">Thoughtful finds for everyday living</p>
        <a class="cart-link" href="index.php#products" aria-label="Shopping bag, <?= $cartCount ?> items">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path></svg>
            <span>Bag</span>
            <span class="cart-count"><?= $cartCount ?></span>
        </a>
    </div>
</header>
<main class="site-main">
    <?php if ($flash !== null): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>
