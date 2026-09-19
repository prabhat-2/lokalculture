<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Lokal Culture</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/vendor.css">
</head>
<body class="dashboard-page">
<header class="site-header">
    <a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a>
    <nav class="header-actions">
        <a href="/vendor/products">Products</a>
        <a href="/logout">Sign out</a>
    </nav>
</header>
<main class="vendor-form-shell">
    <p class="eyebrow">Vendor workspace</p>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <p class="form-intro">New products are reviewed before they appear in the public collection.</p>
    <?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form class="vendor-form" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($action) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <?php if ($editing): ?>
            <?php /* Preserve the existing image path so the controller can fall back to it when no new file is uploaded. */ ?>
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($formData['image']) ?>">
        <?php endif; ?>

        <label>Product name<input name="name" required maxlength="180" value="<?= htmlspecialchars($formData['name']) ?>"></label>
        <label>SKU<input name="sku" required maxlength="80" value="<?= htmlspecialchars($formData['sku']) ?>"></label>
        <label>Category<select name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= htmlspecialchars($category['id']) ?>" <?= (string) $formData['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
            <?php endforeach; ?>
        </select></label>
        <div class="product-tax-field">
            <div>
                <label for="hsn_id">HSN code<select id="hsn_id" name="hsn_id" required>
                    <option value="">Select a code</option>
                    <?php foreach ($hsnCodes as $hsn): ?>
                        <option value="<?= htmlspecialchars($hsn['id']) ?>" data-gst-rate="<?= htmlspecialchars((string) $hsn['gst_rate']) ?>" <?= (string) $formData['hsn_id'] === (string) $hsn['id'] ? 'selected' : '' ?>><?= htmlspecialchars($hsn['hsn_code']) ?><?= $hsn['description'] ? ' · ' . htmlspecialchars($hsn['description']) : '' ?></option>
                    <?php endforeach; ?>
                </select></label>
            </div>
            <div class="gst-readout" aria-live="polite">
                <span>Applicable GST</span>
                <strong id="gst-rate">—</strong>
                <small>Set by the HSN master</small>
            </div>
        </div>
        <div class="form-columns">
            <label>Price<input name="price" type="number" min="1" step="0.01" required value="<?= htmlspecialchars($formData['price']) ?>"></label>
            <label>Stock<input name="stock" type="number" min="0" step="1" required value="<?= htmlspecialchars($formData['stock']) ?>"></label>
        </div>
        <label>Description<textarea name="description" rows="6" required maxlength="2000"><?= htmlspecialchars($formData['description']) ?></textarea></label>

        <?php if ($editing && $formData['image']): ?>
            <div class="current-image">
                <p>Current image:</p>
                <img src="<?= htmlspecialchars($formData['image']) ?>" alt="Current product image" style="max-width:140px;max-height:140px;object-fit:cover;display:block;margin-top:0.4rem;border-radius:4px;">
                <small><?= htmlspecialchars($formData['image']) ?></small>
            </div>
        <?php endif; ?>

        <label><?= $editing ? 'Replace primary image (optional)' : 'Primary image' ?><input name="image_file" type="file" accept="image/jpeg,image/png,image/webp" <?= $editing ? '' : 'required' ?>></label>
        <button class="button" type="submit"><?= $editing ? 'Save changes' : 'Submit product for review' ?> <span>→</span></button>
    </form>
</main>
<script>
    const hsnSelect = document.querySelector('#hsn_id');
    const gstRate = document.querySelector('#gst-rate');

    function updateGstRate() {
        const option = hsnSelect.options[hsnSelect.selectedIndex];
        const rate = option?.dataset.gstRate;
        gstRate.textContent = rate ? `${rate}%` : '—';
    }

    hsnSelect.addEventListener('change', updateGstRate);
    updateGstRate();
</script>
</body>
</html>