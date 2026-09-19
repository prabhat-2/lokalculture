<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Lokal Culture connects you with thoughtful Indian clothing, craft, and homeware from independent makers.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/home-motion.css">
    <link rel="stylesheet" href="/assets/css/home-collections.css">
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Lokal Culture',
        'url' => (string) (getenv('APP_URL') ?: 'http://localhost:8000'),
        'description' => 'A marketplace connecting shoppers with independent Indian makers of clothing, craft, and homeware.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Lokal Culture',
        'url' => (string) (getenv('APP_URL') ?: 'http://localhost:8000'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => rtrim((string) (getenv('APP_URL') ?: 'http://localhost:8000'), '/') . '/shop?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<div class="announcement">Free shipping across India on orders over ₹1,500 <span>•</span> Curated with care</div>
<header class="site-header">
    <a class="brand" href="/" aria-label="Lokal Culture home"><span class="brand-mark">✦</span> LOKAL <em>CULTURE</em></a>
    <div class="search-wrap"><form class="search" action="/shop" method="get" autocomplete="off"><label class="sr-only" for="search">Search products</label><input id="search" name="q" placeholder="Search textiles, craft, colour..." type="search" data-search-input><button aria-label="Search">⌕</button></form><div class="search-suggest" data-search-suggest hidden></div></div>
    <nav class="header-actions" aria-label="Account navigation"><a class="sell-with-us" href="/vendor/register">Sell with us</a><a href="/account">Account</a><a href="/wishlist">Wishlist<?php if ($wishlistCount > 0): ?> <span><?= (int) $wishlistCount ?></span><?php endif; ?></a><a class="bag" href="/cart">Bag <span><?= (int) $cartCount ?></span></a><button class="nav-hamburger" type="button" aria-label="Toggle menu" aria-expanded="false" data-nav-hamburger>☰</button></nav>
</header>
<nav class="category-nav" aria-label="Primary navigation" data-category-nav><?php foreach ($primaryNavigation as $index => $menu): ?><div class="nav-menu" data-nav-menu><a href="<?= htmlspecialchars($menu['href']) ?>" aria-haspopup="true" aria-expanded="false" aria-controls="nav-menu-<?= $index ?>"><?= htmlspecialchars($menu['label']) ?></a><button class="nav-toggle" type="button" aria-label="Show <?= htmlspecialchars($menu['label']) ?> menu" aria-expanded="false" aria-controls="nav-menu-<?= $index ?>">⌄</button><div class="nav-dropdown" id="nav-menu-<?= $index ?>" data-nav-dropdown><?php foreach ($menu['items'] as [$label, $href]): ?><a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a><?php endforeach; ?></div></div><?php endforeach; ?></nav>

<main>
    <section class="hero" aria-label="Featured collection"><div class="hero-slides"><?php foreach ($heroSlides as $index => $slide): ?><div class="hero-slide <?= $index === 0 ? 'active' : '' ?>" style="--hero-image:url('<?= htmlspecialchars($slide['image'], ENT_QUOTES) ?>')" data-hero-slide><img src="<?= htmlspecialchars($slide['image']) ?>" alt="<?= htmlspecialchars($slide['title'] . ' ' . $slide['accent']) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"></div><?php endforeach; ?></div>
        <div class="hero-copy" data-hero-copy><p class="eyebrow" data-hero-eyebrow><?= htmlspecialchars($heroSlides[0]['eyebrow']) ?></p><h1><span data-hero-title><?= htmlspecialchars($heroSlides[0]['title']) ?></span><br><i data-hero-accent><?= htmlspecialchars($heroSlides[0]['accent']) ?></i></h1><p data-hero-description><?= htmlspecialchars($heroSlides[0]['description']) ?></p><a class="button button-light" href="/shop">Explore the collection <span>→</span></a></div>
        <div class="hero-dots"><?php foreach ($heroSlides as $index => $slide): ?><button class="<?= $index === 0 ? 'active' : '' ?>" type="button" aria-label="Show slide <?= $index + 1 ?>" data-hero-dot></button><?php endforeach; ?></div>
    </section>

    <section class="section collection-section reveal"><div class="section-heading"><p class="eyebrow">A little something special</p><h2>Our top collection</h2><p>Seasonal pieces to keep close.</p></div><div class="tabs" role="tablist" aria-label="Top collection"><button class="active" id="collection-tab-for-her" type="button" role="tab" aria-selected="true" aria-controls="collection-panel" tabindex="0" data-collection-tab="for-her">For her</button><button id="collection-tab-for-him" type="button" role="tab" aria-selected="false" aria-controls="collection-panel" tabindex="-1" data-collection-tab="for-him">For him</button><button id="collection-tab-for-home" type="button" role="tab" aria-selected="false" aria-controls="collection-panel" tabindex="-1" data-collection-tab="for-home">For home</button></div><div class="product-grid" id="collection-panel" role="tabpanel" aria-labelledby="collection-tab-for-her" data-collection-panel>
        <?php foreach ($collections as $collection): ?><article class="product-card reveal-child"><div class="product-image"><a href="/product/<?= urlencode($collection['slug']) ?>"><img loading="lazy" src="<?= htmlspecialchars($collection['image']) ?>" alt="<?= htmlspecialchars($collection['name']) ?>"></a><button class="heart" type="button" aria-label="Add <?= htmlspecialchars($collection['name']) ?> to wishlist" aria-pressed="false">♡</button></div><a class="product-card-link" href="/product/<?= urlencode($collection['slug']) ?>"><p class="product-category"><?= htmlspecialchars($collection['category']) ?></p><h3><?= htmlspecialchars($collection['name']) ?></h3><strong><?= htmlspecialchars($collection['price']) ?></strong></a></article><?php endforeach; ?>
    </div><a class="text-link" href="/shop">Shop all collection <span>→</span></a></section>

    <section class="section category-section reveal"><div class="section-heading"><p class="eyebrow">Find your next favourite</p><h2>Top categories</h2><p>Rooted in tradition, made for today.</p></div><div class="category-layout"><div class="category-list"><?php foreach ($categories as $index => $category): ?><a class="<?= $index === 0 ? 'active' : '' ?> reveal-child" href="/shop?category=<?= urlencode($category['slug']) ?>"><span>0<?= $index + 1 ?></span><?= htmlspecialchars($category['name']) ?><b>↗</b></a><?php endforeach; ?></div><div class="category-feature reveal-child"><img loading="lazy" src="https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=1200&q=88" alt="People celebrating together at a colourful gathering"><div><p class="eyebrow">Editor's pick</p><h3>Crafted for celebration</h3><a href="/shop">Shop the edit →</a></div></div></div><div class="category-rail"><?php foreach ($categoryImages as $categoryImage): ?><a class="category-tile reveal-child" href="/shop?category=<?= urlencode($categoryImage['slug']) ?>"><img loading="lazy" src="<?= htmlspecialchars($categoryImage['image']) ?>" alt="<?= htmlspecialchars($categoryImage['name']) ?>"><span><?= htmlspecialchars($categoryImage['name']) ?> <b>↗</b></span></a><?php endforeach; ?></div><?php foreach ($categoryGalleries as $galleryName => $galleryImages): ?><section class="category-gallery reveal"><div class="gallery-heading"><p class="eyebrow">The <?= htmlspecialchars(strtolower($galleryName)) ?> edit</p><h3><?= htmlspecialchars($galleryName) ?></h3></div><div class="gallery-grid"><?php foreach ($galleryImages as $imageId): ?><a class="gallery-image reveal-child" href="/shop?category=<?= urlencode($categorySlugsByName[$galleryName] ?? '') ?>"><img loading="lazy" src="https://images.unsplash.com/<?= htmlspecialchars($imageId) ?>?auto=format&amp;fit=crop&amp;w=700&amp;q=82" alt="<?= htmlspecialchars($galleryName) ?> collection image"></a><?php endforeach; ?></div></section><?php endforeach; ?></section>

    <section class="section vendor-section reveal"><div class="section-heading"><p class="eyebrow">People behind the pieces</p><h2>Meet the makers</h2><p>Small studios, big stories.</p></div><div class="vendor-grid"><?php foreach ($vendors as $vendor): ?><article class="vendor-card reveal-child"><img loading="lazy" src="<?= htmlspecialchars($vendor['image']) ?>" alt="<?= htmlspecialchars($vendor['name']) ?> products"><div><h3><?= htmlspecialchars($vendor['name']) ?></h3><p><?= htmlspecialchars($vendor['type']) ?></p><span>View shop →</span></div></article><?php endforeach; ?></div><a class="text-link" href="/vendors">Meet all makers <span>→</span></a></section>

    <section class="benefits"><div><span>✧</span><strong>Thoughtful shipping</strong><p>Every order packed with care.</p></div><div><span>◌</span><strong>Always human</strong><p>Real support, whenever you need it.</p></div><div><span>↺</span><strong>Easy returns</strong><p>Take your time to get it right.</p></div><div><span>⌁</span><strong>Secure checkout</strong><p>Your details stay yours.</p></div></section>
    <section class="newsletter"><div><p class="eyebrow">A note in your inbox</p><h2>Good things, occasionally.</h2><p>New makers, thoughtful edits, and a little inspiration.</p></div><form><label class="sr-only" for="email">Email address</label><input id="email" type="email" placeholder="Your email address" required><button class="button" type="submit">Sign me up →</button></form></section>
</main>
<footer class="site-footer"><div class="footer-brand"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><p>India, in all its beautiful detail.</p><small>© <?= date('Y') ?> Lokal Culture</small></div><div><h4>Explore</h4><a href="/shop">Shop all</a><a href="/vendors">Our makers</a><a href="/shop">New arrivals</a></div><div><h4>Help</h4><a href="/login">Your account</a><a href="/shop">Shipping & returns</a><a href="/shop">Contact us</a></div><div><h4>Follow along</h4><a href="/">Instagram ↗</a><a href="/">Pinterest ↗</a></div></footer>
<script>document.querySelector('.newsletter form').addEventListener('submit', function (event) { event.preventDefault(); this.innerHTML = '<p class="form-success">You are on the list. Thank you.</p>'; }); (function () { const slides = Array.from(document.querySelectorAll('[data-hero-slide]')); const dots = Array.from(document.querySelectorAll('[data-hero-dot]')); const heroCopy = document.querySelector('[data-hero-copy]'); const data = <?= json_encode($heroSlides, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>; let current = 0; let timer; function showHero(index) { current = (index + slides.length) % slides.length; slides.forEach(function (slide, itemIndex) { slide.classList.toggle('active', itemIndex === current); }); dots.forEach(function (dot, itemIndex) { dot.classList.toggle('active', itemIndex === current); }); document.querySelector('[data-hero-eyebrow]').textContent = data[current].eyebrow; document.querySelector('[data-hero-title]').textContent = data[current].title; document.querySelector('[data-hero-accent]').textContent = data[current].accent; document.querySelector('[data-hero-description]').textContent = data[current].description; if (heroCopy) { heroCopy.classList.remove('hero-animate'); void heroCopy.offsetWidth; heroCopy.classList.add('hero-animate'); } } function restart() { window.clearInterval(timer); timer = window.setInterval(function () { showHero(current + 1); }, 6500); } dots.forEach(function (dot, index) { dot.addEventListener('click', function () { showHero(index); restart(); }); }); showHero(0); restart(); })(); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) { const observer = new IntersectionObserver(function (entries) { entries.forEach(function (entry) { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); } }); }, { threshold: 0.14 }); document.querySelectorAll('.reveal, .reveal-child').forEach(function (element) { observer.observe(element); }); } else { document.querySelectorAll('.reveal, .reveal-child').forEach(function (element) { element.classList.add('is-visible'); }); }</script>
<script>
(() => {
    const collectionData = <?= json_encode($featuredCollections, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const tabs = Array.from(document.querySelectorAll('[data-collection-tab]'));
    const panel = document.querySelector('[data-collection-panel]');

    function productCard(product) {
        const article = document.createElement('article');
        article.className = 'product-card reveal-child is-visible';
        const imageWrap = document.createElement('div');
        imageWrap.className = 'product-image';
        const imageLink = document.createElement('a');
        imageLink.href = `/product/${encodeURIComponent(product.slug)}`;
        const image = document.createElement('img');
        image.loading = 'lazy';
        image.src = product.image;
        image.alt = product.name;
        const heart = document.createElement('button');
        heart.className = 'heart';
        heart.type = 'button';
        heart.setAttribute('aria-label', `Add ${product.name} to wishlist`);
        heart.setAttribute('aria-pressed', 'false');
        heart.textContent = '♡';
        imageLink.append(image);
        imageWrap.append(imageLink, heart);
        const detailsLink = document.createElement('a');
        detailsLink.className = 'product-card-link';
        detailsLink.href = imageLink.href;
        const category = document.createElement('p');
        category.className = 'product-category';
        category.textContent = product.category;
        const name = document.createElement('h3');
        name.textContent = product.name;
        const price = document.createElement('strong');
        price.textContent = product.price;
        detailsLink.append(category, name, price);
        article.append(imageWrap, detailsLink);
        return article;
    }

    function showCollection(key) {
        if (!panel || !collectionData[key]) return;
        panel.replaceChildren(...collectionData[key].map(productCard));
        tabs.forEach((tab) => {
            const selected = tab.dataset.collectionTab === key;
            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
            if (selected) panel.setAttribute('aria-labelledby', tab.id);
        });
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => showCollection(tab.dataset.collectionTab));
        tab.addEventListener('keydown', (event) => {
            let nextIndex = null;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = tabs.length - 1;
            if (nextIndex === null) return;
            event.preventDefault();
            tabs[nextIndex].focus();
            showCollection(tabs[nextIndex].dataset.collectionTab);
        });
    });

    document.addEventListener('click', (event) => {
        const heart = event.target.closest('.heart');
        if (!heart) return;
        const isSaved = heart.getAttribute('aria-pressed') === 'true';
        heart.setAttribute('aria-pressed', String(!isSaved));
        heart.textContent = isSaved ? '♡' : '♥';
        heart.classList.toggle('saved', !isSaved);
        heart.setAttribute('aria-label', isSaved ? 'Add item to wishlist' : 'Remove item from wishlist');
    });

    const menus = Array.from(document.querySelectorAll('[data-nav-menu]'));
    const hamburger = document.querySelector('[data-nav-hamburger]');
    const categoryNav = document.querySelector('[data-category-nav]');
    hamburger?.addEventListener('click', () => {
        const open = categoryNav.classList.toggle('open');
        hamburger.setAttribute('aria-expanded', String(open));
    });
    function closeMenu(menu) {
        menu.classList.remove('is-open');
        menu.querySelector('[aria-haspopup]')?.setAttribute('aria-expanded', 'false');
        menu.querySelector('.nav-toggle')?.setAttribute('aria-expanded', 'false');
    }
    function openMenu(menu) {
        menu.classList.add('is-open');
        menu.querySelector('[aria-haspopup]')?.setAttribute('aria-expanded', 'true');
        menu.querySelector('.nav-toggle')?.setAttribute('aria-expanded', 'true');
    }
    menus.forEach((menu) => {
        menu.addEventListener('mouseenter', () => openMenu(menu));
        menu.addEventListener('mouseleave', () => closeMenu(menu));
        menu.addEventListener('focusin', () => openMenu(menu));
        menu.addEventListener('focusout', () => window.setTimeout(() => {
            if (!menu.contains(document.activeElement)) closeMenu(menu);
        }, 0));
        menu.querySelector('.nav-toggle')?.addEventListener('click', (event) => {
            event.preventDefault();
            menu.classList.contains('is-open') ? closeMenu(menu) : openMenu(menu);
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        menus.forEach(closeMenu);
    });
})();
</script>
<script src="/assets/js/search-suggest.js"></script>
</body>
</html>
