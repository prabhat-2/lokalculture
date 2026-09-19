<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$routes = require __DIR__ . '/../routes/web.php';
$isCatalogRoute = $path === '/shop' || preg_match('#^/(product|vendor)/[a-z0-9-]+$#', $path) === 1 || preg_match('#^/vendor/products/edit/[a-z0-9-]+$#', $path) === 1 || preg_match('#^/(orders|invoice)/[A-Z0-9-]+$#', $path) === 1 || preg_match('#^/admin/vendors/[0-9]+$#', $path) === 1;

if (!isset($routes[$path]) && !$isCatalogRoute) {
    http_response_code(404);
    $pageTitle = 'Page not found';
    require __DIR__ . '/../resources/views/404.php';
    exit;
}

if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    exit("User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /vendor/products/\nSitemap: " . (getenv('APP_URL') ?: 'http://localhost:8000') . "/sitemap.xml\n");
}

if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://localhost:8000'), '/');
    $urls = [$baseUrl . '/', $baseUrl . '/shop', $baseUrl . '/vendors', $baseUrl . '/stories'];
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url) {
        $sitemap .= '<url><loc>' . htmlspecialchars($url, ENT_XML1) . '</loc></url>';
    }
    exit($sitemap . '</urlset>');
}

$pageTitle = 'Lokal Culture | Discover India, beautifully';
$featuredCollections = [
    'for-her' => [
        ['name' => 'Ajrakh Wrap Dress', 'category' => 'Women’s clothing', 'price' => '₹3,299', 'image' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Block-print Kurta Set', 'category' => 'Women’s clothing', 'price' => '₹2,899', 'image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Handwoven Cotton Saree', 'category' => 'Traditional clothing', 'price' => '₹4,499', 'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Silver Jhumkas', 'category' => 'Jewellery', 'price' => '₹1,299', 'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Leather Sling Bag', 'category' => 'Bags', 'price' => '₹2,199', 'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Indigo Stole', 'category' => 'Scarves & stoles', 'price' => '₹1,499', 'image' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Artisan Kolhapuris', 'category' => 'Footwear', 'price' => '₹2,599', 'image' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Ceramic Jewellery Box', 'category' => 'Home accents', 'price' => '₹899', 'image' => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=900&q=85'],
    ],
    'for-him' => [
        ['name' => 'Handloom Cotton Shirt', 'category' => 'Men’s clothing', 'price' => '₹2,499', 'image' => 'https://images.unsplash.com/photo-1603252109303-2751441dd157?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Indigo Overshirt', 'category' => 'Men’s clothing', 'price' => '₹3,299', 'image' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Linen Trousers', 'category' => 'Men’s clothing', 'price' => '₹2,799', 'image' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Leather Card Wallet', 'category' => 'Accessories', 'price' => '₹1,199', 'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Canvas Weekender Bag', 'category' => 'Bags', 'price' => '₹3,499', 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Minimal Dial Watch', 'category' => 'Accessories', 'price' => '₹2,999', 'image' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Handcrafted Loafers', 'category' => 'Footwear', 'price' => '₹3,199', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Cotton Angavastram', 'category' => 'Traditional clothing', 'price' => '₹1,699', 'image' => 'https://images.unsplash.com/photo-1516826957135-700dedea698c?auto=format&fit=crop&w=900&q=85'],
    ],
    'for-home' => [
        ['name' => 'Deccan Stoneware Set', 'category' => 'Tableware', 'price' => '₹1,299', 'image' => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Handwoven Cushion Covers', 'category' => 'Textiles', 'price' => '₹1,599', 'image' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Brass Table Lamp', 'category' => 'Lighting', 'price' => '₹3,799', 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Block-print Table Runner', 'category' => 'Textiles', 'price' => '₹1,199', 'image' => 'https://images.unsplash.com/photo-1549490349-8643362247b5?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Cane Storage Basket', 'category' => 'Storage', 'price' => '₹1,499', 'image' => 'https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Sandalwood Soy Candle', 'category' => 'Candles & fragrance', 'price' => '₹799', 'image' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Terracotta Planter', 'category' => 'Décor', 'price' => '₹999', 'image' => 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?auto=format&fit=crop&w=900&q=85'],
        ['name' => 'Handcrafted Wall Hanging', 'category' => 'Décor', 'price' => '₹2,299', 'image' => 'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=900&q=85'],
    ],
];

$featuredProductProfiles = [
    'Women’s clothing' => ['materials' => 'Breathable cotton and natural dyes', 'craft' => 'Hand block printing and small-batch tailoring', 'care' => 'Gentle cold wash separately; dry in shade.'],
    'Men’s clothing' => ['materials' => 'Handloom cotton and breathable natural fibres', 'craft' => 'Woven in small batches and finished by hand', 'care' => 'Gentle cold wash; dry in shade and warm iron.'],
    'Traditional clothing' => ['materials' => 'Handwoven cotton and natural dyes', 'craft' => 'Slow loom weaving and artisan finishing', 'care' => 'Dry clean or gently hand wash in cold water.'],
    'Jewellery' => ['materials' => 'Hand-finished silver-tone metal and stonework', 'craft' => 'Small-batch metalwork by independent artisans', 'care' => 'Keep dry and store in the supplied pouch.'],
    'Bags' => ['materials' => 'Responsibly sourced leather and cotton lining', 'craft' => 'Cut, stitched, and finished by hand', 'care' => 'Wipe with a soft dry cloth; avoid prolonged sunlight.'],
    'Scarves & stoles' => ['materials' => 'Soft cotton with natural indigo tones', 'craft' => 'Hand-dyed and finished in small batches', 'care' => 'Hand wash cold separately; dry in shade.'],
    'Footwear' => ['materials' => 'Hand-finished leather and cushioned lining', 'craft' => 'Made in small batches by traditional footwear makers', 'care' => 'Wipe clean and air dry away from direct heat.'],
    'Home accents' => ['materials' => 'Hand-thrown ceramic with a matte glaze', 'craft' => 'Shaped, fired, and finished by hand', 'care' => 'Wipe gently with a soft, dry cloth.'],
    'Accessories' => ['materials' => 'Durable natural materials and hand-finished details', 'craft' => 'Small-batch craftsmanship from independent makers', 'care' => 'Store in a dry place and clean with a soft cloth.'],
    'Tableware' => ['materials' => 'High-fired stoneware with food-safe glaze', 'craft' => 'Wheel-thrown and glazed by hand', 'care' => 'Hand wash recommended; handle with care.'],
    'Textiles' => ['materials' => 'Handwoven cotton with natural fibres', 'craft' => 'Woven and block-printed in small batches', 'care' => 'Gentle cold wash; dry in shade.'],
    'Lighting' => ['materials' => 'Brass, cotton shade, and hand-finished fittings', 'craft' => 'Assembled and polished by skilled metalworkers', 'care' => 'Wipe with a dry cloth; switch off before cleaning.'],
    'Storage' => ['materials' => 'Natural cane and woven fibre', 'craft' => 'Handwoven using time-honoured basketry techniques', 'care' => 'Dust regularly and keep away from damp areas.'],
    'Candles & fragrance' => ['materials' => 'Plant-based soy wax and sandalwood fragrance', 'craft' => 'Hand-poured in small batches', 'care' => 'Trim wick before each burn; never leave unattended.'],
    'Décor' => ['materials' => 'Natural clay and hand-finished fibres', 'craft' => 'Made slowly by independent homeware artisans', 'care' => 'Keep dry and dust with a soft cloth.'],
];
$featuredMakers = ['for-her' => ['name' => 'Mitti & Loom', 'origin' => 'Jaipur, Rajasthan'], 'for-him' => ['name' => 'Loom & Field', 'origin' => 'Bengaluru, Karnataka'], 'for-home' => ['name' => 'Deccan Foundry', 'origin' => 'Hyderabad, Telangana']];
foreach ($featuredCollections as $collectionKey => &$collectionItems) {
    foreach ($collectionItems as &$featuredProduct) {
        $profile = $featuredProductProfiles[$featuredProduct['category']] ?? $featuredProductProfiles['Accessories'];
        $maker = $featuredMakers[$collectionKey];
        $featuredProduct['slug'] = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $featuredProduct['name']), '-'));
        $featuredProduct['description'] = $featuredProduct['name'] . ' is a thoughtful, small-batch piece chosen for everyday rituals and celebrations alike.';
        $featuredProduct['materials'] = $profile['materials'];
        $featuredProduct['craft'] = $profile['craft'];
        $featuredProduct['care'] = $profile['care'];
        $featuredProduct['origin'] = $maker['origin'];
        $featuredProduct['maker'] = $maker['name'];
        $featuredProduct['gallery'] = [$featuredProduct['image'], $featuredProduct['image']];
    }
    unset($featuredProduct);
}
unset($collectionItems);

$primaryNavigation = [
    ['label' => 'New arrivals', 'href' => '/shop', 'items' => [['Just in', '/shop'], ['Best sellers', '/shop'], ['Limited editions', '/shop'], ['Seasonal picks', '/shop']]],
    ['label' => 'Clothing', 'href' => '/shop?category=traditional-clothing', 'items' => [['Women’s clothing', '/shop?category=traditional-clothing'], ['Men’s clothing', '/shop?category=traditional-clothing'], ['Dresses', '/shop?category=traditional-clothing'], ['Shirts', '/shop?category=traditional-clothing'], ['Kurtas & sets', '/shop?category=traditional-clothing'], ['Sarees', '/shop?category=traditional-clothing'], ['Jackets', '/shop?category=traditional-clothing'], ['Bottomwear', '/shop?category=traditional-clothing']]],
    ['label' => 'Home', 'href' => '/shop?category=home-furnishing', 'items' => [['Tableware', '/shop?category=home-furnishing'], ['Décor', '/shop?category=home-furnishing'], ['Textiles', '/shop?category=home-furnishing'], ['Lighting', '/shop?category=home-furnishing'], ['Storage', '/shop?category=home-furnishing'], ['Candles & fragrance', '/shop?category=home-furnishing'], ['Kitchen & dining', '/shop?category=home-furnishing'], ['Gifts', '/shop?category=home-furnishing']]],
    ['label' => 'Accessories', 'href' => '/shop?category=fashion-accessories', 'items' => [['Jewellery', '/shop?category=fashion-accessories'], ['Bags', '/shop?category=fashion-accessories'], ['Scarves & stoles', '/shop?category=fashion-accessories'], ['Footwear', '/shop?category=traditional-footwear'], ['Wallets', '/shop?category=fashion-accessories'], ['Watches', '/shop?category=fashion-accessories']]],
    ['label' => 'Independent makers', 'href' => '/vendors', 'items' => [['Featured makers', '/vendors'], ['Textile artists', '/vendors'], ['Ceramic studios', '/vendors'], ['Jewellery designers', '/vendors'], ['Home décor makers', '/vendors'], ['View all makers', '/vendors']]],
    ['label' => 'Sale', 'href' => '/shop', 'items' => [['Up to 30% off', '/shop'], ['Up to 50% off', '/shop'], ['Last chance', '/shop'], ['Sale for her', '/shop?category=traditional-clothing'], ['Sale for him', '/shop?category=traditional-clothing'], ['Sale for home', '/shop?category=home-furnishing']]],
];

$fallbackVendors = [
    ['name' => 'Mitti & Loom', 'type' => 'Handwoven textiles', 'image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=400&q=85'],
    ['name' => 'Deccan Foundry', 'type' => 'Crafted homeware', 'image' => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=400&q=85'],
    ['name' => 'Canvas Art', 'type' => 'Modern Indian art', 'image' => 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=400&q=85'],
];

$fallbackCategories = [
    ['name' => 'Traditional clothing', 'slug' => 'traditional-clothing'],
    ['name' => 'Traditional footwear', 'slug' => 'traditional-footwear'],
    ['name' => 'Home furnishing', 'slug' => 'home-furnishing'],
    ['name' => 'Fashion accessories', 'slug' => 'fashion-accessories'],
];
$categorySlugsByName = array_column($fallbackCategories, 'slug', 'name');
$heroSlides = [
    ['image' => 'https://images.unsplash.com/photo-1609357605129-26f69add5d6e?auto=format&fit=crop&w=2000&q=88', 'eyebrow' => 'The festive edit', 'title' => 'Made here.', 'accent' => 'Meant to last.', 'description' => 'Pieces with a point of view, gathered from independent Indian makers.'],
    ['image' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=2000&q=88', 'eyebrow' => 'A new season of colour', 'title' => 'Wear your', 'accent' => 'wonder.', 'description' => 'Handpicked silhouettes and craft for the days worth dressing up for.'],
    ['image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=2000&q=88', 'eyebrow' => 'The maker edit', 'title' => 'Small studios.', 'accent' => 'Big stories.', 'description' => 'Meet the people and practices shaping contemporary Indian design.'],
];
$categoryImages = [
    ['name' => 'Traditional clothing', 'slug' => 'traditional-clothing', 'image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=700&q=85'],
    ['name' => 'Traditional footwear', 'slug' => 'traditional-footwear', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=85'],
    ['name' => 'Home furnishing', 'slug' => 'home-furnishing', 'image' => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=700&q=85'],
    ['name' => 'Fashion accessories', 'slug' => 'fashion-accessories', 'image' => 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=700&q=85'],
];
$categoryGalleries = [
    'Traditional clothing' => ['photo-1610030469983-98e550d6193c', 'photo-1529139574466-a303027c1d8b', 'photo-1515886657613-9f3515b0c78f', 'photo-1503342217505-b0a15ec3261c', 'photo-1496747611176-843222e1e57c', 'photo-1521572163474-6864f9cf17ab', 'photo-1539109136881-3be0616acf4b', 'photo-1485968579580-b6d095142e6e', 'photo-1509631179647-0177331693ae', 'photo-1483985988355-763728e1935b'],
    'Traditional footwear' => ['photo-1542291026-7eec264c27ff', 'photo-1543163521-1bf539c55dd2', 'photo-1525966222134-fcfa99b8ae77', 'photo-1495555961986-6d4c1ecb7be3', 'photo-1552346154-21d32810aba3', 'photo-1542291026-7eec264c27ff', 'photo-1460353581641-37baddab0fa2', 'photo-1514989940723-e8e51635b782', 'photo-1595950653106-6c9ebd614d3a', 'photo-1520256862855-398228c41684'],
    'Home furnishing' => ['photo-1610701596007-11502861dcfa', 'photo-1505693416388-ac5ce068fe85', 'photo-1494438639946-1ebd1d20bf85', 'photo-1493663284031-b7e3aefcae8e', 'photo-1524758631624-e2822e304c36', 'photo-1513519245088-0e12902e5a38', 'photo-1505693416388-ac5ce068fe85', 'photo-1519710164239-da123dc03ef4', 'photo-1522708323590-d24dbb6b0267', 'photo-1555041469-a586c61ea9bc'],
    'Fashion accessories' => ['photo-1525507119028-ed4c629a60a3', 'photo-1490481651871-ab68de25d43d', 'photo-1515372039744-b8f02a3ae446', 'photo-1512436991641-6745cdb1723f', 'photo-1503342217505-b0a15ec3261c', 'photo-1513519245088-0e12902e5a38', 'photo-1523275335684-37898b6baf30', 'photo-1523779917675-b6ed3a42a561', 'photo-1492707892479-7bc8d5a4ee93', 'photo-1523170335258-f5ed11844a49'],
];

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/HomepageRepository.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/CatalogRepository.php';
require_once __DIR__ . '/../app/ImageUploader.php';
require_once __DIR__ . '/../app/CartRepository.php';
require_once __DIR__ . '/../app/PaymentService.php';
require_once __DIR__ . '/../app/OrderRepository.php';
require_once __DIR__ . '/../app/PayoutRepository.php';
require_once __DIR__ . '/../app/VendorRepository.php';
require_once __DIR__ . '/../app/CustomerRepository.php';
require_once __DIR__ . '/../app/NotificationService.php';
require_once __DIR__ . '/../app/LogisticsService.php';
require_once __DIR__ . '/../app/CulturalContentRepository.php';
require_once __DIR__ . '/../app/IntegrationService.php';
require_once __DIR__ . '/../app/ReviewRepository.php';

try {
    $auth = new Auth(Database::connection());
    $catalog = new CatalogRepository(Database::connection());
    $cart = new CartRepository(Database::connection());
    $payments = new PaymentService(Database::connection());
    $orders = new OrderRepository(Database::connection());
    $payouts = new PayoutRepository(Database::connection());
    $vendorsAdmin = new VendorRepository(Database::connection());
    $customers = new CustomerRepository(Database::connection());
    $notifications = new NotificationService(Database::connection());
    $logistics = new LogisticsService(Database::connection());
    $content = new CulturalContentRepository(Database::connection());
    $integrations = new IntegrationService();
    $reviews = new ReviewRepository(Database::connection());
} catch (PDOException $exception) {
    http_response_code(503);
    exit('Database connection unavailable.');
}

$headerUser = $auth->currentUser();
$cartCount = 0;
$wishlistCount = 0;
if ($headerUser && $headerUser['role'] === 'customer') {
    try {
        $cartCount = $cart->countItems((int) $headerUser['id']);
        $wishlistCount = count($customers->wishlist((int) $headerUser['id']));
    } catch (PDOException $exception) {
        // Header badges are decorative; leave them at zero if the lookup fails.
    }
}

if ($path === '/logout') {
    $auth->logout();
    header('Location: /');
    exit;
}

if ($path === '/search/suggest') {
    header('Content-Type: application/json; charset=UTF-8');
    $term = trim((string) ($_GET['q'] ?? ''));
    $results = mb_strlen($term) >= 2 ? $catalog->suggest($term) : [];
    exit(json_encode(array_map(static function (array $row): array {
        return ['name' => $row['name'], 'category' => $row['category'], 'url' => '/product/' . rawurlencode($row['slug'])];
    }, $results)));
}

if ($path === '/shop') {
    $search = trim((string) ($_GET['q'] ?? ''));
    $category = trim((string) ($_GET['category'] ?? ''));
    $region = trim((string) ($_GET['region'] ?? ''));
    $craft = trim((string) ($_GET['craft'] ?? ''));
    $sort = trim((string) ($_GET['sort'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $products = $catalog->products($search, $category, $region, $craft, $sort, $page);
    $productCount = $catalog->productCount($search, $category, $region, $craft);
    $categories = $catalog->categories();
    $filterOptions = $catalog->filterOptions();
    $pageTitle = 'Shop the collection';
    require __DIR__ . '/../resources/views/shop.php';
    exit;
}

if ($path === '/stories' || preg_match('#^/stories/([a-z0-9-]+)$#', $path, $storyMatch) === 1) {
    $story = isset($storyMatch[1]) ? $content->findApproved($storyMatch[1]) : null;
    if (isset($storyMatch[1]) && !$story) {
        http_response_code(404);
        exit('Story not found.');
    }
    $stories = $story ? [] : $content->approved();
    $whatsappUrl = $integrations->whatsappUrl('Hello Lokal Culture');
    require __DIR__ . '/../resources/views/stories.php';
    exit;
}

if ($path === '/payment/verify') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Payment verification session expired.');
    }
    $verified = $payments->verifyAndCapture((int) $user['id'], (string) ($_POST['order_number'] ?? ''), (string) ($_POST['razorpay_payment_id'] ?? ''), (string) ($_POST['razorpay_order_id'] ?? ''), (string) ($_POST['razorpay_signature'] ?? ''));
    if (!$verified) {
        http_response_code(422);
        exit('Payment verification failed. Your order remains pending.');
    }
    $orderLookup = Database::connection()->prepare('SELECT id FROM orders WHERE customer_id = :customer_id AND order_number = :order_number LIMIT 1');
    $orderLookup->execute(['customer_id' => $user['id'], 'order_number' => $_POST['order_number'] ?? '']);
    $paidOrderId = (int) $orderLookup->fetchColumn();
    if ($paidOrderId > 0) {
        $logistics->prepareVendorShipments($paidOrderId);
    }
    $orderNumber = (string) $_POST['order_number'];
    $paid = true;
    $notifications->orderUpdate((int) $user['id'], (string) $user['email'], 'Payment confirmed: ' . $orderNumber, 'Your Lokal Culture payment for order ' . $orderNumber . ' has been confirmed.');
    require __DIR__ . '/../resources/views/order-confirmation.php';
    exit;
}

if ($path === '/payment/webhook') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Webhook requires POST.');
    }
    $accepted = $payments->handleWebhook((string) file_get_contents('php://input'), (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? ''));
    http_response_code($accepted ? 200 : 400);
    exit($accepted ? 'OK' : 'Invalid webhook.');
}

if ($path === '/payment') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer') {
        header('Location: /account');
        exit;
    }
    $orderNumber = trim((string) ($_GET['order'] ?? ''));
    $statement = Database::connection()->prepare("SELECT o.id, o.order_number AS number, o.grand_total AS amount FROM orders o WHERE o.customer_id = :customer_id AND o.order_number = :order_number AND o.status = 'pending' LIMIT 1");
    $statement->execute(['customer_id' => $user['id'], 'order_number' => $orderNumber]);
    $order = $statement->fetch();
    if (!$order) {
        http_response_code(404);
        exit('Pending order not found.');
    }
    $paymentStatement = Database::connection()->prepare("SELECT provider_reference AS id, amount * 100 AS amount FROM payments WHERE order_id = :order_id AND provider = 'razorpay' AND status = 'created' LIMIT 1");
    $paymentStatement->execute(['order_id' => $order['id']]);
    $payment = $paymentStatement->fetch();
    if (!$payment) {
        http_response_code(409);
        exit('Payment session is not available.');
    }
    $gatewayOrder = ['id' => $payment['id'], 'amount' => (int) $payment['amount'], 'currency' => 'INR'];
    $publicKey = $payments->publicKey();
    $csrfToken = $auth->csrfToken();
    require __DIR__ . '/../resources/views/payment.php';
    exit;
}

if ($path === '/cart/add' || $path === '/cart/update') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer') {
        header('Location: /account');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Your cart session expired.');
    }
    try {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        if ($path === '/cart/add') {
            $cart->add((int) $user['id'], $productId, $quantity);
        } else {
            $cart->update((int) $user['id'], $productId, $quantity);
        }
        header('Location: /cart');
        exit;
    } catch (RuntimeException | InvalidArgumentException $exception) {
        http_response_code(422);
        exit($exception->getMessage());
    }
}

if ($path === '/orders/cancel') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Order session expired.');
    }
    try {
        $orders->cancelForCustomer((int) $user['id'], (string) ($_POST['order_number'] ?? ''));
        header('Location: /orders');
        exit;
    } catch (RuntimeException $exception) {
        http_response_code(422);
        exit($exception->getMessage());
    }
}

if ($path === '/orders/return') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Return session expired.');
    }
    try {
        $orders->requestReturn((int) $user['id'], (string) ($_POST['order_number'] ?? ''), (string) ($_POST['reason'] ?? ''));
        header('Location: /orders/' . urlencode((string) $_POST['order_number']));
        exit;
    } catch (RuntimeException $exception) {
        http_response_code(422);
        exit($exception->getMessage());
    }
}

if (in_array($path, ['/profile', '/addresses', '/addresses/delete', '/wishlist', '/wishlist/toggle'], true)) {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer') {
        header('Location: /account');
        exit;
    }
    if ($path === '/wishlist/toggle') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Wishlist session expired.');
        }
        $customers->toggleWishlist((int) $user['id'], (int) ($_POST['product_id'] ?? 0));
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/wishlist'));
        exit;
    }
    if ($path === '/addresses/delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Address session expired.');
        }
        $customers->deleteAddress((int) $user['id'], (int) ($_POST['address_id'] ?? 0));
        header('Location: /addresses');
        exit;
    }
    $section = $path === '/profile' ? 'profile' : ($path === '/addresses' ? 'addresses' : 'wishlist');
    $error = null;
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Your account session expired. Please try again.';
        } elseif ($section === 'profile') {
            $customers->updateProfile((int) $user['id'], trim((string) ($_POST['name'] ?? '')), trim((string) ($_POST['phone'] ?? '')));
            $message = 'Profile updated.';
            $user = $auth->currentUser();
        } else {
            $address = ['label' => trim((string) ($_POST['label'] ?? 'Home')), 'recipient_name' => trim((string) ($_POST['recipient_name'] ?? '')), 'address_line1' => trim((string) ($_POST['address_line1'] ?? '')), 'city' => trim((string) ($_POST['city'] ?? '')), 'state' => trim((string) ($_POST['state'] ?? '')), 'postal_code' => trim((string) ($_POST['postal_code'] ?? '')), 'phone' => trim((string) ($_POST['phone'] ?? '')), 'is_default' => !empty($_POST['is_default'])];
            if (in_array('', [$address['recipient_name'], $address['address_line1'], $address['city'], $address['state'], $address['postal_code'], $address['phone']], true)) {
                $error = 'Complete every address field.';
            } else {
                $customers->saveAddress((int) $user['id'], $address);
                $message = 'Address saved.';
            }
        }
    }
    $addresses = $section === 'addresses' ? $customers->addresses((int) $user['id']) : [];
    $wishlist = $section === 'wishlist' ? $customers->wishlist((int) $user['id']) : [];
    $csrfToken = $auth->csrfToken();
    $pageTitle = ucfirst($section);
    $heading = $section === 'profile' ? 'Profile details' : ($section === 'addresses' ? 'Saved addresses' : 'Your wishlist');
    require __DIR__ . '/../resources/views/customer-settings.php';
    exit;
}

if (in_array($path, ['/vendor/payouts', '/vendor/payouts/request', '/vendor/bank', '/vendor/report', '/admin/payouts', '/admin/payouts/review'], true)) {
    $user = $auth->currentUser();
    if (!$user) {
        header('Location: /account');
        exit;
    }
    if ($path === '/vendor/bank') {
        if ($user['role'] !== 'vendor') {
            header('Location: /account');
            exit;
        }
        $vendor = $payouts->vendor((int) $user['id']);
        if (!$vendor) {
            http_response_code(403);
            exit('Vendor profile is not configured.');
        }
        $details = $payouts->bankDetails((int) $vendor['id']) ?: ['account_holder' => '', 'bank_name' => '', 'account_number_masked' => '', 'ifsc_code' => ''];
        $vendorState = (string) ($vendor['state'] ?? '');
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $details = array_map('trim', array_intersect_key($_POST, array_flip(['account_holder', 'bank_name', 'account_number_masked', 'ifsc_code'])));
            $vendorState = trim((string) ($_POST['state'] ?? ''));
            if (!$auth->checkCsrf($_POST['csrf_token'] ?? null) || $vendorState === '' || mb_strlen($vendorState) > 100 || !preg_match('/^[0-9]{4}$/', $details['account_number_masked'] ?? '')) {
                $error = 'Enter valid bank details and your registered business state.';
            } else {
                $payouts->saveVendorState((int) $vendor['id'], $vendorState);
                $payouts->saveBankDetails((int) $vendor['id'], $details);
                header('Location: /vendor/payouts');
                exit;
            }
        }
        $csrfToken = $auth->csrfToken();
        require __DIR__ . '/../resources/views/bank.php';
        exit;
    }
    if ($path === '/vendor/payouts/request') {
        if ($user['role'] !== 'vendor' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Payout session expired.');
        }
        $vendor = $payouts->vendor((int) $user['id']);
        if (!$payouts->bankDetails((int) $vendor['id'])) {
            http_response_code(422);
            exit('Add bank details before requesting a payout.');
        }
        try {
            $payouts->request((int) $vendor['id']);
            header('Location: /vendor/payouts');
            exit;
        } catch (RuntimeException $exception) {
            http_response_code(422);
            exit($exception->getMessage());
        }
    }
    if ($path === '/admin/payouts/review') {
        if ($user['role'] !== 'administrator' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Payout review session expired.');
        }
        $payouts->review((int) ($_POST['payout_id'] ?? 0), (string) ($_POST['status'] ?? 'rejected'));
        header('Location: /admin/payouts');
        exit;
    }
    if ($user['role'] === 'vendor' && in_array($path, ['/vendor/payouts', '/vendor/report'], true)) {
        $vendor = $payouts->vendor((int) $user['id']);
        if (!$vendor) {
            http_response_code(403);
            exit('Vendor profile is not configured.');
        }
        $earnings = $payouts->earnings((int) $vendor['id']);
        $eligibleItems = $payouts->eligibleItems((int) $vendor['id']);
        if ($path === '/vendor/report') {
            $grossSales = (float) $earnings['gross_earnings'] + (float) $earnings['platform_commission'];
            $lowStockProducts = $catalog->lowStockForVendor((int) $vendor['id']);
            require __DIR__ . '/../resources/views/report.php';
            exit;
        }
        $payoutsList = $payouts->payouts((int) $vendor['id']);
        $payouts = $payoutsList;
        $pageTitle = 'Vendor payouts';
        $eyebrow = 'Vendor workspace';
        $heading = 'Earnings & payouts';
        $adminView = false;
        $csrfToken = $auth->csrfToken();
        require __DIR__ . '/../resources/views/payouts.php';
        exit;
    }
    if ($path === '/admin/payouts' && $user['role'] === 'administrator') {
        $payoutsList = $payouts->payouts();
        $payouts = $payoutsList;
        $vendor = null;
        $pageTitle = 'Payout review';
        $eyebrow = 'Administrator workspace';
        $heading = 'Vendor payout requests';
        $adminView = true;
        $csrfToken = $auth->csrfToken();
        require __DIR__ . '/../resources/views/payouts.php';
        exit;
    }
    header('Location: /account');
    exit;
}

if ($path === '/orders' || $path === '/vendor/orders' || $path === '/admin/orders') {
    $user = $auth->currentUser();
    if (!$user) {
        header('Location: /account');
        exit;
    }
    if ($path === '/orders' && $user['role'] === 'customer') {
        $ordersList = $orders->forCustomer((int) $user['id']);
        $pageTitle = 'Your orders';
        $eyebrow = 'Customer account';
        $heading = 'Your orders';
    } elseif ($path === '/vendor/orders' && $user['role'] === 'vendor') {
        $ordersList = $orders->forVendor((int) $user['id']);
        $pageTitle = 'Vendor orders';
        $eyebrow = 'Vendor workspace';
        $heading = 'Orders to fulfil';
    } elseif ($path === '/admin/orders' && $user['role'] === 'administrator') {
        $ordersList = $orders->all();
        $pageTitle = 'Order management';
        $eyebrow = 'Administrator workspace';
        $heading = 'All marketplace orders';
    } else {
        header('Location: /account');
        exit;
    }
    $orders = $ordersList;
    require __DIR__ . '/../resources/views/orders.php';
    exit;
}

if ($path === '/admin/orders/status' || $path === '/vendor/orders/status') {
    $user = $auth->currentUser();
    $allowedRole = $path === '/admin/orders/status' ? 'administrator' : 'vendor';
    if (!$user || $user['role'] !== $allowedRole || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Order update session expired.');
    }
    $orderNumber = (string) ($_POST['order_number'] ?? '');
    $order = $allowedRole === 'vendor' ? $orders->details($orderNumber, null, (int) $user['id']) : $orders->details($orderNumber);
    if (!$order) {
        http_response_code(404);
        exit('Order not found.');
    }
    $status = (string) ($_POST['status'] ?? 'processing');
    if ($status === 'refunded') {
        if ($allowedRole !== 'administrator') {
            http_response_code(403);
            exit('Only administrators can issue refunds.');
        }
        try {
            $payments->refundOrder((int) $order['id']);
        } catch (RuntimeException $exception) {
            http_response_code(422);
            exit($exception->getMessage());
        }
    } else {
        $orders->updateStatus((int) $order['id'], $status);
        if (in_array($status, ['shipped', 'delivered'], true)) {
            $customer = $orders->details($orderNumber);
            if ($customer) {
                $notifications->orderUpdate((int) $customer['customer_id'], (string) $customer['email'], 'Order update: ' . $orderNumber, 'Your Lokal Culture order ' . $orderNumber . ' is now ' . $status . '.');
            }
        }
    }
    header('Location: /orders/' . urlencode($orderNumber));
    exit;
}

if ($path === '/admin/orders/expire-pending') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'administrator' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Order cleanup session expired.');
    }
    $orders->expirePendingOrders();
    header('Location: /admin/orders');
    exit;
}

if ($path === '/admin/orders/returns/review') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'administrator' || $_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Return review session expired.');
    }
    $orders->reviewReturn((int) ($_POST['return_id'] ?? 0), (string) ($_POST['status'] ?? 'rejected'));
    header('Location: /admin/orders');
    exit;
}

if (preg_match('#^/orders/([A-Z0-9-]+)$#', $path, $orderMatch) === 1) {
    $user = $auth->currentUser();
    if (!$user) {
        header('Location: /account');
        exit;
    }
    $order = $user['role'] === 'customer' ? $orders->details($orderMatch[1], (int) $user['id']) : ($user['role'] === 'vendor' ? $orders->details($orderMatch[1], null, (int) $user['id']) : ($user['role'] === 'administrator' ? $orders->details($orderMatch[1]) : null));
    if (!$order) {
        http_response_code(404);
        exit('Order not found.');
    }
    $customerView = $user['role'] === 'customer';
    $csrfToken = $auth->csrfToken();
    require __DIR__ . '/../resources/views/order-detail.php';
    exit;
}

if (preg_match('#^/invoice/([A-Z0-9-]+)$#', $path, $invoiceMatch) === 1) {
    $user = $auth->currentUser();
    if (!$user) {
        header('Location: /account');
        exit;
    }
    $order = $user['role'] === 'customer' ? $orders->details($invoiceMatch[1], (int) $user['id']) : ($user['role'] === 'vendor' ? $orders->details($invoiceMatch[1], null, (int) $user['id']) : ($user['role'] === 'administrator' ? $orders->details($invoiceMatch[1]) : null));
    if (!$order) {
        http_response_code(404);
        exit('Invoice not found.');
    }
    require __DIR__ . '/../resources/views/invoice.php';
    exit;
}

if ($path === '/cart' || $path === '/checkout') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'customer') {
        header('Location: /account');
        exit;
    }
    $items = $cart->items((int) $user['id']);
    $subtotal = array_sum(array_column($items, 'line_total'));
    $csrfToken = $auth->csrfToken();
    if ($path === '/cart') {
        $error = null;
        $pageTitle = 'Your bag';
        require __DIR__ . '/../resources/views/cart.php';
        exit;
    }
    $address = ['recipient_name' => $user['name'], 'address_line1' => '', 'city' => '', 'state' => '', 'postal_code' => '', 'phone' => ''];
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($address) as $field) {
            $address[$field] = trim((string) ($_POST[$field] ?? $address[$field]));
        }
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Your checkout session expired. Please try again.';
        } elseif (count(array_filter([$address['recipient_name'], $address['address_line1'], $address['city'], $address['state'], $address['postal_code'], $address['phone']])) !== 6) {
            $error = 'Complete every delivery field before placing your order.';
        } else {
            try {
                $order = $cart->checkout((int) $user['id'], $address);
                try {
                    $gatewayOrder = $payments->createGatewayOrder((int) $order['id'], $order['number'], (float) $order['amount']);
                } catch (RuntimeException $paymentException) {
                    $cart->cancelPendingOrder((int) $order['id']);
                    throw $paymentException;
                }
                header('Location: /payment?order=' . urlencode($order['number']));
                exit;
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }
    }
    $pageTitle = 'Checkout';
    require __DIR__ . '/../resources/views/checkout.php';
    exit;
}

if ($path === '/vendor/products') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'vendor') {
        header('Location: /account');
        exit;
    }
    $vendor = $catalog->vendorForUser((int) $user['id']);
    if (!$vendor) {
        http_response_code(403);
        exit('Vendor profile is not configured.');
    }
    require __DIR__ . '/../resources/views/vendor-products.php';
    exit;
}

if ($path === '/admin/products') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'administrator') {
        header('Location: /account');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Your moderation session expired.');
        }
        $decision = ($_POST['decision'] ?? '') === 'approve' ? 'published' : 'archived';
        $catalog->moderateProduct((int) ($_POST['product_id'] ?? 0), $decision);
        header('Location: /admin/products');
        exit;
    }
    $products = $catalog->pendingProducts();
    $csrfToken = $auth->csrfToken();
    require __DIR__ . '/../resources/views/admin-products.php';
    exit;
}

if ($path === '/admin/stories') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'administrator') {
        header('Location: /account');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Content moderation session expired.');
        }
        $content->moderate((int) ($_POST['content_id'] ?? 0), (string) ($_POST['status'] ?? 'rejected'));
        header('Location: /admin/stories');
        exit;
    }
    $stories = $content->all();
    $csrfToken = $auth->csrfToken();
    require __DIR__ . '/../resources/views/admin-stories.php';
    exit;
}

if ($path === '/admin/vendors' || preg_match('#^/admin/vendors/([0-9]+)$#', $path, $adminVendorMatch) === 1) {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'administrator') {
        header('Location: /account');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Vendor management session expired.');
        }
        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'verify') {
                $status = (string) ($_POST['status'] ?? 'rejected');
                $vendorsAdmin->setVerification($vendorId, $status);
                $vendorRecord = $vendorsAdmin->find($vendorId);
                if ($vendorRecord && !empty($vendorRecord['email'])) {
                    $notifications->orderUpdate(
                        (int) $vendorRecord['user_id'],
                        (string) $vendorRecord['email'],
                        $status === 'approved' ? 'Your Lokal Culture shop is approved' : 'Your Lokal Culture application was declined',
                        $status === 'approved'
                            ? 'Good news — ' . $vendorRecord['shop_name'] . ' is now live on Lokal Culture. Sign in to add your first products.'
                            : 'Your application for ' . $vendorRecord['shop_name'] . ' was not approved at this time.'
                    );
                }
            } elseif ($action === 'active') {
                $vendorsAdmin->setActive($vendorId, ($_POST['is_active'] ?? '') === '1');
            } elseif ($action === 'commission') {
                $vendorsAdmin->updateCommission($vendorId, (float) ($_POST['commission_rate'] ?? 0));
            }
            header('Location: /admin/vendors/' . $vendorId);
            exit;
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            exit($exception->getMessage());
        }
    }
    $csrfToken = $auth->csrfToken();
    if (isset($adminVendorMatch[1])) {
        $vendor = $vendorsAdmin->find((int) $adminVendorMatch[1]);
        if (!$vendor) {
            http_response_code(404);
            exit('Vendor not found.');
        }
        require __DIR__ . '/../resources/views/admin-vendor-detail.php';
        exit;
    }
    $adminVendors = $vendorsAdmin->all();
    require __DIR__ . '/../resources/views/admin-vendors.php';
    exit;
}

if ($path === '/admin/categories') {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'administrator') {
        header('Location: /account');
        exit;
    }
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Your category session expired. Please try again.';
        } else {
            try {
                $action = (string) ($_POST['action'] ?? '');
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                if ($action === 'archive') {
                    $catalog->archiveCategory($categoryId);
                } else {
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
                    $parentId = (int) ($_POST['parent_id'] ?? 0) ?: null;
                    if ($name === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                        throw new InvalidArgumentException('Enter a category name and a lowercase slug.');
                    }
                    if ($action === 'update') {
                        $catalog->updateCategory($categoryId, $name, $slug, $parentId);
                    } else {
                        $catalog->createCategory($name, $slug, $parentId);
                    }
                }
                header('Location: /admin/categories');
                exit;
            } catch (PDOException | InvalidArgumentException $exception) {
                $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'That category slug may already be in use.';
            }
        }
    }
    $categories = $catalog->adminCategories();
    $csrfToken = $auth->csrfToken();
    require __DIR__ . '/../resources/views/admin-categories.php';
    exit;
}

if ($path === '/vendor/products/new' || preg_match('#^/vendor/products/edit/([a-z0-9-]+)$#', $path, $productMatch) === 1) {
    $user = $auth->currentUser();
    if (!$user || $user['role'] !== 'vendor') {
        header('Location: /account');
        exit;
    }
    $vendor = $catalog->vendorForUser((int) $user['id']);
    $editing = $path !== '/vendor/products/new';
    $existing = $vendor && $editing ? $catalog->productForVendor((int) $vendor['id'], $productMatch[1]) : null;
    if (!$vendor || ($editing && !$existing)) {
        http_response_code(404);
        exit('Product not found.');
    }
    $formData = $existing ?: ['name' => '', 'sku' => '', 'category_id' => '', 'hsn_id' => '', 'price' => '', 'stock' => '0', 'description' => '', 'image' => ''];
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formData = array_merge($formData, [
            'name'        => trim((string) ($_POST['name'] ?? '')),
            'sku'         => trim((string) ($_POST['sku'] ?? '')),
            'category_id' => (string) ($_POST['category_id'] ?? ''),
            'hsn_id'      => (string) ($_POST['hsn_id'] ?? ''),
            'price'       => trim((string) ($_POST['price'] ?? '')),
            'stock'       => trim((string) ($_POST['stock'] ?? '0')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            // 'image' is intentionally NOT merged from $_POST.
            // The existing path is preserved via the 'existing_image' hidden field below.
        ]);
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Your form session expired. Please try again.';
        } elseif ($formData['name'] === '' || $formData['sku'] === '' || $formData['description'] === '' || filter_var($formData['hsn_id'], FILTER_VALIDATE_INT) === false) {
            $error = 'Complete the product name, SKU, and description.';
        } elseif (!is_numeric($formData['price']) || (float) $formData['price'] <= 0 || filter_var($formData['stock'], FILTER_VALIDATE_INT) === false || (int) $formData['stock'] < 0) {
            $error = 'Enter a valid positive price and a non-negative stock quantity.';
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $formData['name']), '-'));
            try {
                $uploadedFile = $_FILES['image_file'] ?? null;
                $hasNewUpload = $uploadedFile && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

                if ($editing) {
                    // Preserve the existing image when no new file is uploaded.
                    // Fall back to the hidden-field value posted by the form.
                    $existingImagePath = trim((string) ($_POST['existing_image'] ?? ''));
                    $image = $hasNewUpload
                        ? (new ImageUploader(__DIR__ . '/uploads/products'))->store($uploadedFile)
                        : $existingImagePath;
                } else {
                    // New product — upload is always required (enforced by HTML 'required').
                    $image = (new ImageUploader(__DIR__ . '/uploads/products'))->store($uploadedFile ?? []);
                }
                $hsn = $catalog->activeHsnCode((int) $formData['hsn_id']);
                if (!$hsn) {
                    throw new RuntimeException('Select an active HSN code.');
                }
                $data = ['category_id' => (int) $formData['category_id'], 'hsn_id' => (int) $hsn['id'], 'name' => $formData['name'], 'sku' => $formData['sku'], 'description' => $formData['description'], 'price' => (float) $formData['price'], 'image' => $image, 'stock' => (int) $formData['stock']];
                if ($editing) {
                    $catalog->updateProduct((int) $vendor['id'], (int) $existing['id'], $data);
                } else {
                    $data['slug'] = $slug . '-' . strtolower(substr(bin2hex(random_bytes(3)), 0, 6));
                    $catalog->createProduct((int) $vendor['id'], $data);
                }
                header('Location: /vendor/products');
                exit;
            } catch (PDOException | RuntimeException $exception) {
                $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'This SKU may already be in use. Check the details and try again.';
            }
        }
    }
    $categories = $catalog->categories();
    $hsnCodes = $catalog->activeHsnCodes();
    $csrfToken = $auth->csrfToken();
    $pageTitle = $editing ? 'Edit product' : 'Add a product';
    $action = $editing ? '/vendor/products/edit/' . urlencode($existing['slug']) : '/vendor/products/new';
    require __DIR__ . '/../resources/views/vendor-product-form.php';
    exit;
}

if (preg_match('#^/product/([a-z0-9-]+)$#', $path, $matches) === 1) {
    $product = $catalog->product($matches[1]);
    $isFeaturedProduct = false;
    if (!$product) {
        foreach ($featuredCollections as $collectionItems) {
            foreach ($collectionItems as $featuredProduct) {
                if ($featuredProduct['slug'] !== $matches[1]) {
                    continue;
                }
                $product = $featuredProduct + [
                    'id' => 0,
                    'images' => $featuredProduct['gallery'],
                    'stock' => 0,
                    'vendor' => $featuredProduct['maker'],
                    'vendor_slug' => '',
                ];
                $isFeaturedProduct = true;
                break 2;
            }
        }
    }
    if (!$product) {
        http_response_code(404);
        $pageTitle = 'Product not found';
        require __DIR__ . '/../resources/views/404.php';
        exit;
    }
    $pageTitle = $product['name'];
    $csrfToken = $auth->csrfToken();
    $currentUser = $auth->currentUser();
    $canAddToCart = !$isFeaturedProduct && $currentUser && $currentUser['role'] === 'customer';
    $relatedProducts = !$isFeaturedProduct && !empty($product['id']) ? $catalog->relatedProducts((int) $product['id']) : [];
    $reviewError = null;
    $productReviews = [];
    $reviewSummary = ['review_count' => 0, 'average_rating' => 0.0];
    $canReview = false;
    if (!$isFeaturedProduct && !empty($product['id'])) {
        $productReviews = $reviews->forProduct((int) $product['id']);
        $reviewSummary = $reviews->summary((int) $product['id']);
        $canReview = $currentUser && $currentUser['role'] === 'customer' && !$reviews->hasReviewed((int) $product['id'], (int) $currentUser['id']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_rating'])) {
            if (!$currentUser || $currentUser['role'] !== 'customer') {
                $reviewError = 'Sign in as a customer to leave a review.';
            } elseif (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
                $reviewError = 'Your review session expired. Please try again.';
            } else {
                $rating = (int) ($_POST['review_rating'] ?? 0);
                $comment = trim((string) ($_POST['review_comment'] ?? ''));
                try {
                    $reviews->add((int) $product['id'], (int) $currentUser['id'], $rating, $comment);
                    header('Location: /product/' . urlencode($product['slug']) . '#reviews');
                    exit;
                } catch (InvalidArgumentException $exception) {
                    $reviewError = $exception->getMessage();
                }
            }
            $productReviews = $reviews->forProduct((int) $product['id']);
            $reviewSummary = $reviews->summary((int) $product['id']);
        }
    }
    require __DIR__ . '/../resources/views/product.php';
    exit;
}

if (preg_match('#^/vendor/(?!register$)([a-z0-9-]+)$#', $path, $matches) === 1) {
    $vendor = $catalog->vendor($matches[1]);
    if (!$vendor) {
        http_response_code(404);
        $pageTitle = 'Maker not found';
        require __DIR__ . '/../resources/views/404.php';
        exit;
    }
    require __DIR__ . '/../resources/views/vendor.php';
    exit;
}

if ($path === '/vendors') {
    $homepage = new HomepageRepository(Database::connection());
    $vendors = $homepage->directoryVendors();
    $pageTitle = 'Independent makers';
    require __DIR__ . '/../resources/views/vendors.php';
    exit;
}

if ($path === '/vendor/register') {
    if ($auth->currentUser()) {
        header('Location: /account');
        exit;
    }
    $formData = ['name' => '', 'email' => '', 'shop_name' => '', 'description' => '', 'gstin' => '', 'registered_address' => '', 'state' => '', 'postal_code' => '', 'region' => '', 'craft_specialty' => ''];
    $error = null;
    $success = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($formData) as $field) {
            $formData[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $password = (string) ($_POST['password'] ?? '');
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Your form session expired. Please try again.';
        } elseif (in_array('', [$formData['name'], $formData['email'], $formData['shop_name'], $formData['gstin'], $formData['registered_address'], $formData['state'], $formData['postal_code']], true) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Complete the required business and contact fields.';
        } elseif (strlen($password) < 8 || $password !== (string) ($_POST['password_confirmation'] ?? '')) {
            $error = 'Passwords must match and contain at least 8 characters.';
        } else {
            $registration = $auth->registerVendor($formData['name'], $formData['email'], $password, $formData);
            if (isset($registration['error'])) {
                $error = $registration['error'];
            } else {
                $success = 'Your vendor application has been submitted for review.';
                $formData = array_fill_keys(array_keys($formData), '');
            }
        }
    }
    $csrfToken = $auth->csrfToken();
    $pageTitle = 'Become a maker';
    require __DIR__ . '/../resources/views/vendor-register.php';
    exit;
}

if ($path === '/login' || $path === '/register' || $path === '/dashboard') {
    $mode = $path === '/register' ? 'register' : 'login';
    header('Location: /account?mode=' . $mode);
    exit;
}

if ($path === '/account') {
    $user = $auth->currentUser();
    if ($user) {
        $dashboardContent = [
            'customer' => ['heading' => 'Your account', 'description' => 'Keep track of orders, saved pieces, and your details.', 'cards' => [['icon' => '↗', 'title' => 'Your orders', 'description' => 'Order history and delivery updates.', 'href' => '/orders'], ['icon' => '♡', 'title' => 'Wishlist', 'description' => 'Pieces you want to keep close.', 'href' => '/wishlist'], ['icon' => '⌂', 'title' => 'Addresses', 'description' => 'Manage your delivery details.', 'href' => '/addresses'], ['icon' => '◎', 'title' => 'Profile', 'description' => 'Edit your personal details.', 'href' => '/profile']]],
            'vendor' => ['heading' => 'Your shop', 'description' => 'Your maker workspace for products, orders, and earnings.', 'cards' => [['icon' => '+', 'title' => 'Products', 'description' => 'Add and manage your catalog.', 'href' => '/vendor/products'], ['icon' => '↗', 'title' => 'Orders', 'description' => 'Review and process customer orders.', 'href' => '/vendor/orders'], ['icon' => '₹', 'title' => 'Earnings', 'description' => 'Track commission and payouts.', 'href' => '/vendor/payouts']]],
            'administrator' => ['heading' => 'Marketplace overview', 'description' => 'Manage the people and pieces that make Lokal Culture.', 'cards' => [['icon' => '◎', 'title' => 'Vendors', 'description' => 'Review shops and verification.', 'href' => '/admin/vendors'], ['icon' => '▦', 'title' => 'Catalog', 'description' => 'Approve products and categories.', 'href' => '/admin/products'], ['icon' => '↗', 'title' => 'Orders', 'description' => 'Monitor marketplace activity.', 'href' => '/admin/orders'], ['icon' => '₹', 'title' => 'Payouts', 'description' => 'Review vendor payout requests.', 'href' => '/admin/payouts']]],
        ][$user['role']] ?? null;
        if (!$dashboardContent) {
            http_response_code(403);
            exit('This account does not have a dashboard role.');
        }
        $pageTitle = $dashboardContent['heading'];
        $heading = $dashboardContent['heading'];
        $description = $dashboardContent['description'];
        $cards = $dashboardContent['cards'];
        $recentOrder = null;
        $wishlistPreview = [];
        $vendorStateMissing = false;
        $adminSummary = null;
        $customerFirstName = trim(explode(' ', (string) $user['name'])[0] ?? '');
        if ($user['role'] === 'customer') {
            $recentOrder = $orders->forCustomer((int) $user['id'])[0] ?? null;
            $wishlistPreview = array_slice($customers->wishlist((int) $user['id']), 0, 2);
            $heading = 'Welcome back, ' . ($customerFirstName !== '' ? $customerFirstName : 'there') . '.';
            $description = 'Manage your orders, saved pieces, and account details.';
        } elseif ($user['role'] === 'vendor') {
            $vendorProfile = $payouts->vendor((int) $user['id']);
            $vendorStateMissing = !$vendorProfile || trim((string) ($vendorProfile['state'] ?? '')) === '';
        } elseif ($user['role'] === 'administrator') {
            $adminSummary = $vendorsAdmin->dashboardSummary();
        }
        require __DIR__ . '/../resources/views/account.php';
        exit;
    }

    $requestedForm = $_POST['form'] ?? $_GET['mode'] ?? 'login';
    $form = $requestedForm === 'register' ? 'register' : 'login';
    $error = null;
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$auth->checkCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Your form session expired. Please try again.';
        } elseif ($form === 'login') {
            if ($auth->attempt((string) $email, (string) ($_POST['password'] ?? ''))) {
                header('Location: /account');
                exit;
            }
            $error = 'The email or password is incorrect.';
        } else {
            $password = (string) ($_POST['password'] ?? '');
            if (trim((string) $name) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Enter your name and a valid email address.';
            } elseif (strlen($password) < 8) {
                $error = 'Your password must contain at least 8 characters.';
            } elseif ($password !== (string) ($_POST['password_confirmation'] ?? '')) {
                $error = 'Your passwords do not match.';
            } else {
                $registration = $auth->registerCustomer(trim((string) $name), (string) $email, $password);
                if (isset($registration['error'])) {
                    $error = $registration['error'];
                } else {
                    header('Location: /account');
                    exit;
                }
            }
        }
    }

    $pageTitle = $form === 'login' ? 'Sign in' : 'Create your account';
    $eyebrow = $form === 'login' ? 'Welcome back' : 'Join the community';
    $heading = $form === 'login' ? 'Come on in.' : 'Find your place here.';
    $csrfToken = $auth->csrfToken();
    require __DIR__ . '/../resources/views/account.php';
    exit;
}

$collections = $featuredCollections['for-her'];
$vendors = $fallbackVendors;
$categories = $fallbackCategories;

try {
    $homepage = new HomepageRepository(Database::connection());
    $collections = $homepage->collections() ?: $collections;
    $vendors = $homepage->vendors() ?: $fallbackVendors;
    $categories = $homepage->categories() ?: $fallbackCategories;
    $stories = $content->approved(3);
} catch (PDOException $exception) {
    error_log('Homepage database unavailable: ' . $exception->getMessage());
}

require __DIR__ . '/../resources/views/home.php';
