<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Lokal Culture</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/catalog.css">
</head>
<body>
<header class="site-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav class="header-actions"><a href="/shop">Shop</a><a href="/account">Account</a></nav></header>
<main class="vendor-shell">
    <p class="eyebrow">The people behind the pieces</p>
    <h1>Independent makers</h1>
    <p class="vendor-description">Small studios and skilled hands shaping contemporary Indian design.</p>
    <div class="vendor-products">
        <?php foreach ($vendors as $vendor): ?>
            <article class="catalog-card">
                <a href="/vendor/<?= urlencode($vendor['slug']) ?>">
                    <div class="catalog-image"><img loading="lazy" src="<?= htmlspecialchars($vendor['image']) ?>" alt="<?= htmlspecialchars($vendor['name']) ?>"></div>
                    <p>Independent maker</p>
                    <h2><?= htmlspecialchars($vendor['name']) ?></h2>
                    <small><?= htmlspecialchars($vendor['type'] ?? '') ?></small>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$vendors): ?><p class="empty-state">Maker profiles are coming soon.</p><?php endif; ?>
</main>
</body>
</html>