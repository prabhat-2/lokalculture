<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($product['name']) ?> | Lokal Culture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/catalog.css">
    <link rel="stylesheet" href="/assets/css/storefront-phase3.css">
    <?php if (!$isFeaturedProduct && !empty($product['id'])): ?>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => $product['description'],
        'image' => $product['images'],
        'category' => $product['category'],
        'brand' => ['@type' => 'Brand', 'name' => $product['vendor'] ?? 'Lokal Culture'],
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'INR',
            'price' => preg_replace('/[^0-9.]/', '', (string) $product['price']),
            'availability' => ((int) ($product['stock'] ?? 0)) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
</head>
<body>
<header class="site-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav class="header-actions"><a href="/shop">Shop</a><a href="/wishlist">Wishlist</a><?php if ($canAddToCart): ?><a href="/cart">Bag</a><?php endif; ?><a href="/account">Account</a></nav></header>
<main class="product-shell product-detail">
    <a class="back-link" href="/shop">← Back to collection</a>
    <div class="product-detail-layout">
        <div class="product-gallery" data-product-gallery>
            <div class="product-gallery-main"><img data-gallery-main src="<?= htmlspecialchars($product['images'][0] ?? '') ?>" alt="<?= htmlspecialchars($product['name']) ?>" fetchpriority="high"></div>
            <?php if (count($product['images']) > 1): ?><div class="product-gallery-thumbs"><?php foreach ($product['images'] as $index => $image): ?><button type="button" class="product-gallery-thumb <?= $index === 0 ? 'active' : '' ?>" data-gallery-thumb data-image="<?= htmlspecialchars($image) ?>"><img loading="lazy" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['name']) ?> view <?= $index + 1 ?>"></button><?php endforeach; ?></div><?php endif; ?>
        </div>
        <section class="product-info"><p class="eyebrow"><?= htmlspecialchars($product['category']) ?></p><h1><?= htmlspecialchars($product['name']) ?></h1><?php if (!$isFeaturedProduct): ?><a class="product-rating-summary" href="#reviews"><span class="stars" aria-hidden="true"><?= str_repeat('★', (int) round($reviewSummary['average_rating'])) . str_repeat('☆', 5 - (int) round($reviewSummary['average_rating'])) ?></span><?= $reviewSummary['review_count'] > 0 ? number_format($reviewSummary['average_rating'], 1) . ' · ' . $reviewSummary['review_count'] . ' review' . ($reviewSummary['review_count'] === 1 ? '' : 's') : 'Be the first to review' ?></a><?php endif; ?><p class="product-price"><?= htmlspecialchars($product['price']) ?></p><p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
            <dl class="product-details"><div><dt>Materials</dt><dd><?= htmlspecialchars($product['materials'] ?? 'Details supplied by the maker.') ?></dd></div><div><dt>Craft</dt><dd><?= htmlspecialchars($product['craft'] ?? ($product['craft_specialty'] ?? 'Made in a small independent studio.')) ?></dd></div><div><dt>Region</dt><dd><?= htmlspecialchars($product['origin'] ?? ($product['region'] ?? 'India')) ?></dd></div><div><dt>HSN / GST</dt><dd><?= htmlspecialchars($product['hsn_code'] ?? '—') ?> · <?= isset($product['gst_rate']) ? number_format((float) $product['gst_rate'], 2) . '%' : 'Tax details at checkout' ?></dd></div><div><dt>Care</dt><dd><?= htmlspecialchars($product['care'] ?? 'Follow the maker’s care guidance.') ?></dd></div></dl>
            <?php if ($isFeaturedProduct): ?><p class="availability-note">This artisan piece is coming soon. Join the collection to hear when it arrives.</p><button class="button" type="button" disabled>Coming soon</button><button class="wishlist-button" type="button" aria-pressed="false">♡ Save for later</button><?php elseif ($product['stock'] > 0 && $canAddToCart): ?><form class="purchase-form" method="post" action="/cart/add"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>"><label>Quantity<input class="product-quantity" type="number" name="quantity" min="1" max="<?= htmlspecialchars($product['stock']) ?>" value="1"></label><button class="button" type="submit">Add to bag <span>→</span></button></form><form method="post" action="/wishlist/toggle" class="wishlist-action"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>"><button type="submit">♡ Save to wishlist</button></form><?php elseif ($product['stock'] > 0): ?><a class="button" href="/account?mode=login">Sign in to add to bag <span>→</span></a><?php else: ?><button class="button" type="button" disabled>Currently unavailable</button><?php endif; ?>
            <aside class="maker-note"><strong>Made with care</strong><p>Small-batch craftsmanship, thoughtfully selected for your home and wardrobe.</p><?php if (!empty($product['vendor_slug'])): ?><p>Made by <a href="/vendor/<?= urlencode($product['vendor_slug']) ?>"><?= htmlspecialchars($product['vendor']) ?></a></p><?php else: ?><p>Made by <?= htmlspecialchars($product['maker'] ?? $product['vendor']) ?></p><?php endif; ?></aside>
            <p class="delivery-note">✧ Thoughtfully packed and shipped across India.</p>
        </section>
    </div>
</main>
<?php if (!empty($relatedProducts)): ?><section class="related-products"><p class="eyebrow">You may also like</p><h2>From the same edit</h2><div class="catalog-grid"><?php foreach ($relatedProducts as $related): ?><article class="catalog-card"><a href="/product/<?= urlencode($related['slug']) ?>"><div class="catalog-image"><img loading="lazy" src="<?= htmlspecialchars($related['image']) ?>" alt="<?= htmlspecialchars($related['name']) ?>"></div><p><?= htmlspecialchars($related['category']) ?></p><h2><?= htmlspecialchars($related['name']) ?></h2><strong><?= htmlspecialchars($related['price']) ?></strong></a></article><?php endforeach; ?></div></section><?php endif; ?>
<?php if (!$isFeaturedProduct && !empty($product['id'])): ?>
<section class="product-reviews" id="reviews">
    <p class="eyebrow">Customer reviews</p>
    <h2>What people are saying</h2>
    <div class="reviews-summary"><span class="stars" aria-hidden="true"><?= str_repeat('★', (int) round($reviewSummary['average_rating'])) . str_repeat('☆', 5 - (int) round($reviewSummary['average_rating'])) ?></span><strong><?= number_format($reviewSummary['average_rating'], 1) ?></strong><span><?= $reviewSummary['review_count'] ?> review<?= $reviewSummary['review_count'] === 1 ? '' : 's' ?></span></div>
    <?php if ($reviewError): ?><p class="auth-error"><?= htmlspecialchars($reviewError) ?></p><?php endif; ?>
    <?php if ($canReview): ?>
        <form class="review-form" method="post" action="/product/<?= urlencode($product['slug']) ?>#reviews">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>Your rating
                <select name="review_rating" required>
                    <option value="">Select</option>
                    <?php for ($star = 5; $star >= 1; $star--): ?><option value="<?= $star ?>"><?= $star ?> star<?= $star === 1 ? '' : 's' ?></option><?php endfor; ?>
                </select>
            </label>
            <label>Your review<textarea name="review_comment" rows="3" maxlength="1000" placeholder="Share how the piece looked, felt, and fit your everyday life."></textarea></label>
            <button class="button" type="submit">Submit review <span>→</span></button>
        </form>
    <?php endif; ?>
    <ul class="review-list">
        <?php foreach ($productReviews as $review): ?>
            <li class="review-item">
                <span class="stars" aria-hidden="true"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></span>
                <strong><?= htmlspecialchars($review['reviewer']) ?></strong>
                <?php if (trim((string) $review['comment']) !== ''): ?><p><?= htmlspecialchars($review['comment']) ?></p><?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if (!$productReviews): ?><li class="review-empty">No reviews yet — be the first to share your experience.</li><?php endif; ?>
    </ul>
</section>
<?php endif; ?>
<script>document.querySelector('.wishlist-button')?.addEventListener('click', function () { const saved = this.getAttribute('aria-pressed') === 'true'; this.setAttribute('aria-pressed', String(!saved)); this.textContent = saved ? '♡ Save for later' : '♥ Saved for later'; });
document.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
        document.querySelector('[data-gallery-main]').src = thumb.dataset.image;
        document.querySelectorAll('[data-gallery-thumb]').forEach(function (t) { t.classList.remove('active'); });
        thumb.classList.add('active');
    });
});</script>
</body>
</html>
