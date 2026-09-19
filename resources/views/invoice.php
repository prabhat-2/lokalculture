<?php

/**
 * Lokal Culture — printable GST tax invoice.
 *
 * Presentation only: every value below is read from $order (OrderRepository::details()) or derived
 * from those same values for display (sums of stored per-item tax columns, amount in words).
 * No amount is recalculated and no value is invented — fields the current backend does not supply
 * (separate invoice number, PAN, seller GSTIN, state code, reverse-charge flag) are only rendered
 * when the order payload already carries them.
 */

if (!function_exists('lokal_inr_number_words')) {
    function lokal_inr_number_words(int $number): string
    {
        $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = [2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];

        if ($number < 20) {
            return $units[$number];
        }
        if ($number < 100) {
            return $tens[intdiv($number, 10)] . ($number % 10 !== 0 ? ' ' . $units[$number % 10] : '');
        }
        if ($number < 1000) {
            return $units[intdiv($number, 100)] . ' Hundred' . ($number % 100 !== 0 ? ' ' . lokal_inr_number_words($number % 100) : '');
        }
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $value => $label) {
            if ($number >= $value) {
                return lokal_inr_number_words(intdiv($number, $value)) . ' ' . $label . ($number % $value !== 0 ? ' ' . lokal_inr_number_words($number % $value) : '');
            }
        }

        return (string) $number;
    }
}

if (!function_exists('lokal_inr_amount_in_words')) {
    function lokal_inr_amount_in_words(float $amount): string
    {
        $rounded = round(abs($amount), 2);
        $rupees = (int) floor($rounded);
        $paise = (int) round(($rounded - $rupees) * 100);
        if ($paise === 100) {
            $rupees++;
            $paise = 0;
        }
        $words = ($rupees === 0 ? 'Zero' : lokal_inr_number_words($rupees)) . ' Rupees';
        if ($paise > 0) {
            $words .= ' and ' . lokal_inr_number_words($paise) . ' Paise';
        }

        return $words . ' Only';
    }
}

$invoiceItems = $order['items'] ?? [];
$invoiceNumber = (string) $order['order_number'];
$invoiceCreatedAt = trim((string) ($order['created_at'] ?? ''));
$invoiceDate = $invoiceCreatedAt !== '' ? date('d M Y', strtotime($invoiceCreatedAt)) : '—';
$invoiceCustomerName = (string) ($order['recipient_name'] ?? $order['customer'] ?? '');
$invoiceState = trim((string) ($order['state'] ?? ''));
$taxableFor = static function (array $item): float {
    return (float) ($item['taxable_amount'] ?? ((float) $item['unit_price'] * (int) $item['quantity']));
};
$totalFor = static function (array $item) use ($taxableFor): float {
    return $taxableFor($item) + (float) ($item['gst_amount'] ?? 0);
};
$taxTypeFor = static function (array $item): string {
    if ((float) ($item['cgst_amount'] ?? 0) > 0 || (float) ($item['sgst_amount'] ?? 0) > 0) {
        return 'CGST + SGST';
    }
    if ((float) ($item['igst_amount'] ?? 0) > 0) {
        return 'IGST';
    }

    return '—';
};
$cgstTotal = (float) array_sum(array_column($invoiceItems, 'cgst_amount'));
$sgstTotal = (float) array_sum(array_column($invoiceItems, 'sgst_amount'));
$igstTotal = (float) array_sum(array_column($invoiceItems, 'igst_amount'));
$taxTotal = (float) ($order['tax_total'] ?? ($cgstTotal + $sgstTotal + $igstTotal));
$discountTotal = (float) ($order['discount_total'] ?? 0);
$shippingTotal = (float) ($order['shipping_total'] ?? 0);
$invoiceSellers = array_values(array_unique(array_filter(array_map(
    static fn (array $item): string => trim((string) ($item['vendor'] ?? '')),
    $invoiceItems
))));
$invoiceCarrier = trim((string) ($order['carrier'] ?? '')) !== ''
    ? trim((string) $order['carrier'])
    : trim((string) (($order['shipments'][0]['carrier'] ?? '')));
$invoiceTracking = trim((string) ($order['tracking_number'] ?? '')) !== ''
    ? trim((string) $order['tracking_number'])
    : trim((string) (($order['shipments'][0]['tracking_number'] ?? '')));

/* Line items sharing an HSN code and GST rate are grouped so the tax breakup mirrors a GST invoice. */
$hsnSummary = [];
foreach ($invoiceItems as $item) {
    $key = ((string) ($item['hsn_code'] ?? '—')) . '|' . number_format((float) ($item['gst_rate'] ?? 0), 2);
    if (!isset($hsnSummary[$key])) {
        $hsnSummary[$key] = [
            'hsn' => (string) ($item['hsn_code'] ?? '—'),
            'rate' => (float) ($item['gst_rate'] ?? 0),
            'taxable' => 0.0,
            'cgst' => 0.0,
            'sgst' => 0.0,
            'igst' => 0.0,
        ];
    }
    $hsnSummary[$key]['taxable'] += $taxableFor($item);
    $hsnSummary[$key]['cgst'] += (float) ($item['cgst_amount'] ?? 0);
    $hsnSummary[$key]['sgst'] += (float) ($item['sgst_amount'] ?? 0);
    $hsnSummary[$key]['igst'] += (float) ($item['igst_amount'] ?? 0);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tax Invoice <?= htmlspecialchars($invoiceNumber) ?> | Lokal Culture</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/invoice.css">
</head>
<body>
<main class="invoice">
    <article class="invoice-sheet">
        <header class="invoice-header">
            <p class="invoice-brand-name">✦ LOKAL <em>CULTURE</em></p>
            <div class="invoice-doctype">
                <p class="invoice-doctype-title">Tax Invoice / Bill of Supply</p>
                <p class="invoice-doctype-sub">(Original for Recipient)</p>
            </div>
        </header>

        <section class="invoice-parties">
            <div class="invoice-col">
                <div class="invoice-block">
                    <h2>Sold By:</h2>
                    <?php if ($invoiceSellers): ?>
                        <?php foreach ($invoiceSellers as $seller): ?>
                            <p class="invoice-party-name"><?= htmlspecialchars($seller) ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="invoice-party-name">Lokal Culture Marketplace Seller</p>
                    <?php endif; ?>
                    <?php /* Statutory seller fields print only when the order payload supplies them. */ ?>
                    <?php if (!empty($order['vendor_address'])): ?><p class="invoice-party-line"><?= htmlspecialchars((string) $order['vendor_address']) ?></p><?php endif; ?>
                    <?php if (!empty($order['vendor_state']) || !empty($order['vendor_postal_code'])): ?>
                        <p class="invoice-party-line"><?= htmlspecialchars(trim((string) ($order['vendor_state'] ?? ''))) ?><?= trim((string) ($order['vendor_postal_code'] ?? '')) !== '' ? ', Pincode ' . htmlspecialchars((string) $order['vendor_postal_code']) : '' ?></p>
                    <?php endif; ?>
                    <p class="invoice-party-line">IN</p>
                    <?php if (!empty($order['vendor_pan'])): ?><p class="invoice-party-line"><b>PAN No:</b> <?= htmlspecialchars((string) $order['vendor_pan']) ?></p><?php endif; ?>
                    <?php if (!empty($order['vendor_gstin'])): ?><p class="invoice-party-line"><b>GST Registration No:</b> <?= htmlspecialchars((string) $order['vendor_gstin']) ?></p><?php endif; ?>
                </div>

                <div class="invoice-block invoice-block-order">
                    <p class="invoice-party-line"><b>Order Number:</b> <?= htmlspecialchars($invoiceNumber) ?></p>
                    <p class="invoice-party-line"><b>Order Date:</b> <?= htmlspecialchars($invoiceDate) ?></p>
                </div>
            </div>

            <div class="invoice-col">
                <div class="invoice-block">
                    <h2>Billing Address:</h2>
                    <p class="invoice-party-name"><?= htmlspecialchars($invoiceCustomerName) ?></p>
                    <p class="invoice-party-line"><?= htmlspecialchars((string) ($order['address_line1'] ?? '')) ?></p>
                    <p class="invoice-party-line"><?= htmlspecialchars(trim((string) ($order['city'] ?? '') . ', ' . $invoiceState)) ?><?= trim((string) ($order['postal_code'] ?? '')) !== '' ? ', Pincode ' . htmlspecialchars((string) $order['postal_code']) : '' ?></p>
                    <p class="invoice-party-line">IN</p>
                    <?php if (!empty($order['state_code'])): ?><p class="invoice-party-line"><b>State/UT Code:</b> <?= htmlspecialchars((string) $order['state_code']) ?></p><?php endif; ?>
                </div>

                <div class="invoice-block">
                    <h2>Shipping Address:</h2>
                    <p class="invoice-party-name"><?= htmlspecialchars($invoiceCustomerName) ?></p>
                    <p class="invoice-party-line"><?= htmlspecialchars((string) ($order['address_line1'] ?? '')) ?></p>
                    <p class="invoice-party-line"><?= htmlspecialchars(trim((string) ($order['city'] ?? '') . ', ' . $invoiceState)) ?><?= trim((string) ($order['postal_code'] ?? '')) !== '' ? ', Pincode ' . htmlspecialchars((string) $order['postal_code']) : '' ?></p>
                    <p class="invoice-party-line">IN</p>
                    <?php if (!empty($order['state_code'])): ?><p class="invoice-party-line"><b>State/UT Code:</b> <?= htmlspecialchars((string) $order['state_code']) ?></p><?php endif; ?>
                    <p class="invoice-party-line"><b>Place of supply:</b> <?= htmlspecialchars($invoiceState !== '' ? strtoupper($invoiceState) : '—') ?></p>
                    <p class="invoice-party-line"><b>Place of delivery:</b> <?= htmlspecialchars((string) ($order['city'] ?? '') !== '' ? strtoupper((string) $order['city']) : '—') ?></p>
                    <?php if ($invoiceCarrier !== '' || $invoiceTracking !== ''): ?>
                        <p class="invoice-party-line"><b>Carrier:</b> <?= htmlspecialchars($invoiceCarrier !== '' ? $invoiceCarrier : '—') ?><?= $invoiceTracking !== '' ? ' · ' . htmlspecialchars($invoiceTracking) : '' ?></p>
                    <?php endif; ?>
                </div>

                <div class="invoice-block invoice-block-order">
                    <p class="invoice-party-line"><b>Invoice Number:</b> <?= htmlspecialchars($invoiceNumber) ?></p>
                    <p class="invoice-party-line"><b>Invoice Details:</b> <?= htmlspecialchars(ucfirst((string) $order['status'])) ?></p>
                    <p class="invoice-party-line"><b>Invoice Date:</b> <?= htmlspecialchars($invoiceDate) ?></p>
                </div>
            </div>
        </section>

        <section class="invoice-lines" aria-label="Invoice items">
            <table class="invoice-items">
                <thead>
                    <tr>
                        <th class="col-sl">Sl.<br>No</th>
                        <th class="col-desc">Description</th>
                        <th class="col-num">Unit<br>Price</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-num">Net<br>Amount</th>
                        <th class="col-rate">Tax<br>Rate</th>
                        <th class="col-type">Tax<br>Type</th>
                        <th class="col-num">Tax<br>Amount</th>
                        <th class="col-num">Total<br>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoiceItems as $index => $item): ?>
                        <tr>
                            <td class="col-sl"><?= (int) $index + 1 ?></td>
                            <td class="col-desc">
                                <span class="item-name"><?= htmlspecialchars((string) $item['product_name']) ?></span>
                                <span class="item-vendor">HSN <?= htmlspecialchars((string) ($item['hsn_code'] ?? '—')) ?><?= trim((string) ($item['vendor'] ?? '')) !== '' ? ' · ' . htmlspecialchars((string) $item['vendor']) : '' ?></span>
                            </td>
                            <td class="col-num">₹<?= number_format((float) $item['unit_price'], 2) ?></td>
                            <td class="col-qty"><?= htmlspecialchars((string) $item['quantity']) ?></td>
                            <td class="col-num">₹<?= number_format($taxableFor($item), 2) ?></td>
                            <td class="col-rate"><?= number_format((float) ($item['gst_rate'] ?? 0), 2) ?>%</td>
                            <td class="col-type"><?= htmlspecialchars($taxTypeFor($item)) ?></td>
                            <td class="col-num">₹<?= number_format((float) ($item['gst_amount'] ?? 0), 2) ?></td>
                            <td class="col-num">₹<?= number_format($totalFor($item), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$invoiceItems): ?>
                        <tr><td class="invoice-empty" colspan="9">No items on this invoice.</td></tr>
                    <?php endif; ?>
                    <?php if ($invoiceItems): ?>
                        <tr class="invoice-total-row">
                            <td colspan="4">TOTAL:</td>
                            <td class="col-num">₹<?= number_format((float) $order['subtotal'], 2) ?></td>
                            <td colspan="2"></td>
                            <td class="col-num">₹<?= number_format($taxTotal, 2) ?></td>
                            <td class="col-num">₹<?= number_format((float) $order['grand_total'], 2) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($hsnSummary && count($hsnSummary) > 1): ?>
                <table class="invoice-hsn">
                    <caption>Tax breakup by HSN</caption>
                    <thead>
                        <tr><th>HSN</th><th>GST</th><th class="col-num">Taxable</th><th class="col-num">CGST</th><th class="col-num">SGST</th><th class="col-num">IGST</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hsnSummary as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['hsn']) ?></td>
                                <td><?= number_format($row['rate'], 2) ?>%</td>
                                <td class="col-num">₹<?= number_format($row['taxable'], 2) ?></td>
                                <td class="col-num">₹<?= number_format($row['cgst'], 2) ?></td>
                                <td class="col-num">₹<?= number_format($row['sgst'], 2) ?></td>
                                <td class="col-num">₹<?= number_format($row['igst'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="invoice-words-box">
            <div class="invoice-words-left">
                <p><b>Amount in Words:</b></p>
                <p class="invoice-words-value"><?= htmlspecialchars(lokal_inr_amount_in_words((float) $order['grand_total'])) ?></p>
            </div>
            <div class="invoice-words-right">
                <p><b>For <?= htmlspecialchars($invoiceSellers[0] ?? 'Lokal Culture Marketplace Seller') ?>:</b></p>
                <div class="invoice-signature-space" aria-hidden="true"></div>
                <p class="invoice-signatory-label">Authorized Signatory</p>
            </div>
        </section>

        <?php /* No reverse-charge column exists in the current schema, so this prints only if supplied. */ ?>
        <?php if (array_key_exists('reverse_charge', $order) && $order['reverse_charge'] !== null): ?>
            <p class="invoice-reverse">Whether tax is payable under reverse charge - <?= ((int) $order['reverse_charge']) === 1 ? 'Yes' : 'No' ?></p>
        <?php else: ?>
            <p class="invoice-reverse">Whether tax is payable under reverse charge - No</p>
        <?php endif; ?>

        <footer class="invoice-footer">
            <p class="invoice-footer-note">This is a computer-generated tax invoice issued by the marketplace seller named above for order <?= htmlspecialchars($invoiceNumber) ?>. Please note that this invoice is not a demand for payment.</p>
            <p class="invoice-page">Page 1 of <span data-invoice-pages>1</span></p>
        </footer>
    </article>

    <div class="invoice-actions no-print">
        <button type="button" onclick="window.print()">Print invoice</button>
    </div>
</main>
<script>
(function () {
    var sheet = document.querySelector('.invoice-sheet');
    var label = document.querySelector('[data-invoice-pages]');
    if (!sheet || !label) {
        return;
    }
    var refresh = function () {
        var millimetresToPixels = 96 / 25.4;
        var printableHeight = 297 * millimetresToPixels - 20 * millimetresToPixels;
        label.textContent = Math.max(1, Math.ceil(sheet.getBoundingClientRect().height / printableHeight));
    };
    window.addEventListener('beforeprint', refresh);
    window.addEventListener('resize', refresh);
    refresh();
})();
</script>
</body>
</html>