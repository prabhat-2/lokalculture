<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vendor management | Lokal Culture</title><link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/vendor.css"><link rel="stylesheet" href="/assets/css/admin.css"></head>
<body class="dashboard-page">
<header class="site-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav class="header-actions"><a href="/account">Dashboard</a><a href="/admin/products">Products</a><a href="/logout">Sign out</a></nav></header>
<main class="vendor-management">
    <div class="management-heading"><div><p class="eyebrow">Administrator workspace</p><h1>Vendors</h1><p>Review maker applications, GST details, and shop activity.</p></div><a class="button" href="/admin/categories">Manage categories <span>→</span></a></div>
    <div class="management-table"><div class="management-row management-header"><span>Vendor</span><span>Verification</span><span>GST</span><span>Products</span><span>Commission</span></div>
    <?php foreach ($adminVendors as $vendor): ?><a class="management-row" href="/admin/vendors/<?= htmlspecialchars((string) $vendor['id']) ?>"><span class="managed-product"><b><?= htmlspecialchars($vendor['shop_name']) ?></b><small><?= htmlspecialchars($vendor['contact_name']) ?></small></span><span><i class="status-<?= htmlspecialchars($vendor['verification_status']) ?>"><?= htmlspecialchars(ucfirst($vendor['verification_status'])) ?></i><small><?= $vendor['is_active'] ? 'Active' : 'Inactive' ?></small></span><span><?= htmlspecialchars($vendor['gst_status']) ?><small><?= htmlspecialchars($vendor['gstin'] ?? 'Not provided') ?></small></span><span><?= htmlspecialchars((string) $vendor['product_count']) ?></span><strong><?= number_format((float) $vendor['commission_rate'], 2) ?>%</strong></a><?php endforeach; ?></div>
    <?php if (!$adminVendors): ?><p class="empty-state">No vendor applications yet.</p><?php endif; ?>
</main>
</body>
</html>
