<?php

declare(strict_types=1);

/**
 * Lokal Culture — customer billing / delivery details step.
 *
 * Presentation only: the form posts the same fields to the same /checkout route with the same
 * CSRF token, and every value comes from the existing $address, $items, $subtotal and $error
 * variables provided by public/index.php. GST is calculated by CartRepository::checkout(), so this
 * page only lists the HSN and GST rate data the cart already carries — no tax amount is computed here.
 */

$gstGroups = [];
foreach ($items as $item) {
    $rate = number_format((float) ($item['gst_rate'] ?? 0), 2);
    $gstGroups[$rate] = ($gstGroups[$rate] ?? 0) + (float) $item['line_total'];
}

$indianStates = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana',
    'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
    'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana',
    'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir',
    'Ladakh', 'Lakshadweep', 'Puducherry',
];
?>
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
<link rel="stylesheet" href="/assets/css/commerce.css">
<link rel="stylesheet" href="/assets/css/billing.css">
</head>
<body class="billing-body">
<header class="site-header"><a class="brand" href="/">✦ LOKAL <em>CULTURE</em></a><nav class="header-actions"><a href="/cart">Back to bag</a><a href="/account">Account</a></nav></header>
<main class="billing-page">
    <div class="billing-heading">
        <p class="eyebrow">Secure checkout</p>
        <h1>Billing &amp; delivery details</h1>
        <p>Your order will be created as pending until payment is connected.</p>
    </div>
    <?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div class="billing-layout">
        <form class="billing-form" method="post" action="/checkout">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <section class="billing-card">
                <header class="billing-card-head">
                    <span class="billing-step">01</span>
                    <div>
                        <h2>Billing Address</h2>
                        <p>The name and address printed on your tax invoice.</p>
                    </div>
                </header>
                <div class="billing-fields">
                    <label class="billing-field billing-field-wide">
                        <span>Recipient name</span>
                        <input name="recipient_name" required value="<?= htmlspecialchars($address['recipient_name']) ?>">
                    </label>
                    <label class="billing-field">
                        <span>Phone</span>
                        <input name="phone" required inputmode="tel" autocomplete="tel" value="<?= htmlspecialchars($address['phone']) ?>">
                    </label>
                    <label class="billing-field billing-field-wide">
                        <span>Address</span>
                        <input name="address_line1" required autocomplete="street-address" value="<?= htmlspecialchars($address['address_line1']) ?>">
                    </label>
                </div>
            </section>

            <section class="billing-card">
                <header class="billing-card-head">
                    <span class="billing-step">02</span>
                    <div>
                        <h2>Delivery &amp; Place of Supply</h2>
                        <p>GST is applied as CGST + SGST for intra-state supply, or IGST for inter-state supply.</p>
                    </div>
                </header>
                <div class="billing-fields">
                    <label class="billing-field">
                        <span>City</span>
                        <input name="city" required autocomplete="address-level2" value="<?= htmlspecialchars($address['city']) ?>">
                    </label>
                    <label class="billing-field">
                        <span>State / UT (place of supply)</span>
                        <input name="state" required list="indian-states" autocomplete="address-level1" value="<?= htmlspecialchars($address['state']) ?>">
                    </label>
                    <label class="billing-field">
                        <span>PIN / postal code</span>
                        <input name="postal_code" required inputmode="numeric" autocomplete="postal-code" value="<?= htmlspecialchars($address['postal_code']) ?>">
                    </label>
                </div>
                <p class="billing-hint">Enter the state exactly as it appears on your GST registration (or your delivery state) — it decides whether CGST + SGST or IGST applies.</p>
            </section>

            <section class="billing-card billing-card-note">
                <header class="billing-card-head">
                    <span class="billing-step">03</span>
                    <div>
                        <h2>GST &amp; HSN on this order</h2>
                        <p>Rates below come from the HSN master attached to each product.</p>
                    </div>
                </header>
                <?php if ($items): ?>
                    <ul class="billing-hsn-list">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <span class="billing-hsn-name"><?= htmlspecialchars($item['name']) ?></span>
                                <span class="billing-hsn-meta">HSN <?= htmlspecialchars((string) ($item['hsn_code'] ?? '—')) ?> · GST <?= number_format((float) ($item['gst_rate'] ?? 0), 2) ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="billing-hint">Your bag is empty, so there is nothing to invoice yet.</p>
                <?php endif; ?>
            </section>

            <button class="button billing-submit" type="submit">Place pending order <span>→</span></button>
            <p class="billing-terms">By placing this order you confirm the billing and delivery details above are correct.</p>
        </form>

        <aside class="billing-summary">
            <div class="billing-summary-head">
                <p class="eyebrow">Order summary</p>
                <h2><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></h2>
            </div>

            <?php if ($items): ?>
                <ul class="billing-lines">
                    <?php foreach ($items as $item): ?>
                        <li class="billing-line">
                            <?php if (trim((string) $item['image']) !== ''): ?>
                                <img loading="lazy" src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <span class="billing-line-placeholder" aria-hidden="true">✦</span>
                            <?php endif; ?>
                            <div class="billing-line-body">
                                <span class="billing-line-vendor"><?= htmlspecialchars($item['vendor']) ?></span>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <span class="billing-line-meta">Qty <?= htmlspecialchars((string) $item['quantity']) ?> · HSN <?= htmlspecialchars((string) ($item['hsn_code'] ?? '—')) ?> · GST <?= number_format((float) ($item['gst_rate'] ?? 0), 2) ?>%</span>
                            </div>
                            <span class="billing-line-total">₹<?= number_format((float) $item['line_total'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="billing-summary-empty">Your bag is waiting. <a href="/shop">Browse the collection</a>.</p>
            <?php endif; ?>

            <div class="billing-totals">
                <div class="billing-total-row">
                    <span>Subtotal (taxable amount)</span>
                    <strong>₹<?= number_format((float) $subtotal, 2) ?></strong>
                </div>
                <?php foreach ($gstGroups as $rate => $taxable): ?>
                    <div class="billing-total-row billing-total-muted">
                        <span>GST <?= htmlspecialchars((string) $rate) ?>% on ₹<?= number_format($taxable, 2) ?></span>
                        <span>Applied on order</span>
                    </div>
                <?php endforeach; ?>
                <div class="billing-total-row billing-total-muted">
                    <span>Tax type</span>
                    <span>CGST + SGST / IGST</span>
                </div>
                <div class="billing-total-row billing-total-grand">
                    <span>Order total</span>
                    <strong>₹<?= number_format((float) $subtotal, 2) ?></strong>
                </div>
                <p class="billing-total-note">Amounts exclude GST. The taxable value, CGST, SGST or IGST and the final payable total are captured on the order and shown on the tax invoice.</p>
            </div>

            <ul class="billing-assurances">
                <li>Secure payment through Razorpay</li>
                <li>HSN and GST rate stored on every invoice line</li>
                <li>Order stays pending until payment is confirmed</li>
            </ul>
        </aside>
    </div>
</main>
<datalist id="indian-states">
    <?php foreach ($indianStates as $stateName): ?>
        <option value="<?= htmlspecialchars($stateName) ?>"></option>
    <?php endforeach; ?>
</datalist>
</body>
</html>