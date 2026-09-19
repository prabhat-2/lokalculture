<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($order['order_number']) ?> | Lokal Culture</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/orders.css">
</head>
<body class="dashboard-page">
<header class="site-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav class="header-actions"><a href="/orders">All orders</a><a href="/account">Dashboard</a></nav></header>
<main class="order-detail-shell">
    <div class="detail-heading"><div><p class="eyebrow">Order details</p><h1><?= htmlspecialchars($order['order_number']) ?></h1><p><?= htmlspecialchars(date('d M Y, H:i', strtotime($order['created_at']))) ?></p></div><i class="status-<?= htmlspecialchars($order['status']) ?> detail-status"><?= htmlspecialchars(ucfirst($order['status'])) ?></i></div>
    <div class="detail-layout">
        <section class="detail-items"><?php foreach ($order['items'] as $item): ?><div class="detail-item"><div><h2><?= htmlspecialchars($item['product_name']) ?></h2><p><?= htmlspecialchars($item['vendor']) ?> · <?= htmlspecialchars((string) $item['quantity']) ?> × ₹<?= number_format((float) $item['unit_price'], 0) ?></p></div><strong>₹<?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 0) ?></strong></div><?php endforeach; ?></section>
        <aside class="order-summary"><p class="eyebrow">Summary</p><div><span>Total</span><strong>₹<?= number_format((float) $order['grand_total'], 0) ?></strong></div><p><?= htmlspecialchars($order['recipient_name'] ?? $order['customer']) ?><br><?= htmlspecialchars($order['address_line1'] ?? '') ?><br><?= htmlspecialchars($order['city'] ?? '') ?>, <?= htmlspecialchars($order['state'] ?? '') ?> <?= htmlspecialchars($order['postal_code'] ?? '') ?></p><?php if (!empty($order['shipment_status'])): ?><p class="shipment-note"><strong>Shipment:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['shipment_status']))) ?><?php if (!empty($order['tracking_number'])): ?><br>Tracking: <?= htmlspecialchars($order['tracking_number']) ?><?php endif; ?></p><?php endif; ?>
        <?php if ($customerView && in_array($order['status'], ['pending', 'paid'], true)): ?><form method="post" action="/orders/cancel"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="order_number" value="<?= htmlspecialchars($order['order_number']) ?>"><button class="button button-muted" type="submit">Cancel order</button></form><?php elseif (!$customerView): ?><form method="post" action="<?= $user['role'] === 'vendor' ? '/vendor/orders/status' : '/admin/orders/status' ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="order_number" value="<?= htmlspecialchars($order['order_number']) ?>"><select name="status"><option value="processing">Processing</option><option value="shipped">Shipped</option><option value="delivered">Delivered</option><?php if ($user['role'] === 'administrator'): ?><option value="refunded">Refunded</option><option value="cancelled">Cancelled</option><?php endif; ?></select><button class="button" type="submit">Update status</button></form><?php endif; ?><a class="invoice-link" href="/invoice/<?= urlencode($order['order_number']) ?>">View printable invoice →</a></aside>
    </div>
</main>
</body>
</html>
