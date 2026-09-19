<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Lokal Culture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/account.css">
    <link rel="stylesheet" href="/assets/css/account-dashboard.css">
</head>
<body class="auth-page">
<header class="account-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav aria-label="Account actions"><a href="/">Back to shop</a><?php if ($user): ?><a href="/logout">Sign out</a><?php endif; ?></nav></header>
<main class="account-shell">
    <?php if (!$user): ?>
        <section class="auth-panel account-panel">
            <div class="account-tabs"><a class="<?= $form === 'login' ? 'active' : '' ?>" href="/account?mode=login">Sign in</a><a class="<?= $form === 'register' ? 'active' : '' ?>" href="/account?mode=register">Create account</a></div>
            <p class="eyebrow"><?= htmlspecialchars($eyebrow) ?></p><h1><?= htmlspecialchars($heading) ?></h1>
            <?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post" action="/account" class="auth-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="form" value="<?= htmlspecialchars($form) ?>">
                <?php if ($form === 'register'): ?><label>Full name<input type="text" name="name" required autocomplete="name" value="<?= htmlspecialchars($name ?? '') ?>"></label><?php endif; ?>
                <label>Email address<input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($email ?? '') ?>"></label>
                <label>Password<input type="password" name="password" minlength="8" required autocomplete="<?= $form === 'login' ? 'current-password' : 'new-password' ?>"></label>
                <?php if ($form === 'register'): ?><label>Confirm password<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></label><?php endif; ?>
                <button class="button" type="submit"><?= $form === 'login' ? 'Sign in' : 'Create account' ?> <span>→</span></button>
            </form>
        </section>
    <?php else: ?>
        <section class="account-dashboard">
            <header class="dashboard-hero"><p class="eyebrow"><?= htmlspecialchars(ucfirst($user['role'])) ?> dashboard</p><h1><?= htmlspecialchars($heading) ?></h1><p class="dashboard-intro"><?= htmlspecialchars($description) ?></p></header>
            <nav class="dashboard-grid" aria-label="Account dashboard"><?php foreach ($cards as $card): ?><?php if (!empty($card['href'])): ?><a class="account-card" href="<?= htmlspecialchars($card['href']) ?>"><?php else: ?><div class="account-card"><?php endif; ?><span class="account-card-icon" aria-hidden="true"><?= htmlspecialchars($card['icon']) ?></span><span class="account-card-copy"><span class="account-card-title"><?= htmlspecialchars($card['title']) ?></span><span class="account-card-description"><?= htmlspecialchars($card['description']) ?></span></span><span class="account-card-action">View <b>→</b></span><?php if (!empty($card['href'])): ?></a><?php else: ?></div><?php endif; ?><?php endforeach; ?></nav>

            <?php if ($user['role'] === 'vendor' && $vendorStateMissing): ?><aside class="dashboard-notice" aria-live="polite"><div><p class="eyebrow">Action required</p><strong>Add your registered business state</strong><p>Orders cannot calculate GST jurisdiction until your vendor state is configured.</p></div><a class="panel-link" href="/vendor/bank">Open vendor settings <b>→</b></a></aside><?php endif; ?>

            <?php if ($user['role'] === 'administrator' && $adminSummary): ?><section class="dashboard-overview" aria-label="Marketplace summary"><article class="dashboard-panel"><p class="eyebrow">Marketplace</p><h2><?= htmlspecialchars((string) $adminSummary['vendors']) ?> vendors · <?= htmlspecialchars((string) $adminSummary['products']) ?> products</h2><p class="panel-copy"><?= htmlspecialchars((string) $adminSummary['pending_vendors']) ?> vendor applications awaiting review. <?= htmlspecialchars((string) $adminSummary['categories']) ?> active categories.</p><a class="panel-link" href="/admin/vendors">Review vendors <b>→</b></a></article><article class="dashboard-panel"><p class="eyebrow">Trading</p><h2>₹<?= number_format($adminSummary['sales'], 2) ?></h2><p class="panel-copy"><?= htmlspecialchars((string) $adminSummary['orders']) ?> orders · ₹<?= number_format($adminSummary['gst'], 2) ?> GST · ₹<?= number_format($adminSummary['commission'], 2) ?> commissions.</p><a class="panel-link" href="/admin/orders">View orders <b>→</b></a></article></section><?php endif; ?>

            <?php if ($user['role'] === 'customer'): ?>
                <section class="dashboard-overview" aria-label="Account highlights">
                    <article class="dashboard-panel recent-order"><div class="panel-heading"><span class="panel-icon" aria-hidden="true">□</span><div><p class="eyebrow">Your recent order</p><h2><?= $recentOrder ? 'A little update' : 'Nothing here just yet' ?></h2></div></div>
                        <?php if ($recentOrder): ?><div class="order-summary"><div><strong><?= htmlspecialchars($recentOrder['order_number']) ?></strong><span><?= htmlspecialchars(date('j M Y', strtotime((string) $recentOrder['created_at']))) ?></span></div><span class="order-status status-<?= htmlspecialchars($recentOrder['status']) ?>"><?= htmlspecialchars(ucfirst($recentOrder['status'])) ?></span></div><p class="panel-copy">Order total <strong>₹<?= number_format((float) $recentOrder['grand_total'], 0) ?></strong></p><a class="panel-link" href="/orders/<?= urlencode($recentOrder['order_number']) ?>">View order <b>→</b></a><?php else: ?><p class="panel-copy">No orders yet. Discover something made with care.</p><a class="button dashboard-button" href="/shop">Start shopping <span>→</span></a><?php endif; ?>
                    </article>
                    <article class="dashboard-panel saved-pieces"><div class="panel-heading"><span class="panel-icon" aria-hidden="true">♡</span><div><p class="eyebrow">Saved for later</p><h2><?= $wishlistPreview ? 'Pieces you love' : 'Your wishlist is waiting' ?></h2></div></div>
                        <?php if ($wishlistPreview): ?><div class="wishlist-preview"><?php foreach ($wishlistPreview as $item): ?><a href="/product/<?= urlencode($item['slug']) ?>"><img loading="lazy" src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"><span><?= htmlspecialchars($item['name']) ?></span></a><?php endforeach; ?></div><a class="panel-link" href="/wishlist">View wishlist <b>→</b></a><?php else: ?><p class="panel-copy">Save pieces you want to return to.</p><a class="panel-link" href="/shop">Explore the collection <b>→</b></a><?php endif; ?>
                    </article>
                </section>
                <section class="account-benefits" aria-label="Lokal Culture service benefits"><div><span aria-hidden="true">✧</span><strong>Thoughtful shipping</strong><p>Every order packed with care.</p></div><div><span aria-hidden="true">↺</span><strong>Easy returns</strong><p>Take your time to get it right.</p></div><div><span aria-hidden="true">⌁</span><strong>Secure checkout</strong><p>Your details stay yours.</p></div><div><span aria-hidden="true">◌</span><strong>Always human</strong><p>Real support when you need it.</p></div></section>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
