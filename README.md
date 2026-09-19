# Lokal Culture Marketplace

A staged PHP/MySQL foundation for a responsive multi-vendor ecommerce marketplace.

## Current foundation

- Public marketplace homepage matching the supplied reference direction
- Separate customer, vendor, and administrator role model in the schema
- Product, vendor, inventory, order, payment, commission, and CMS foundations
- Responsive Bootstrap-compatible CSS design tokens and layout primitives
- PHP front controller that can run without a framework while the local PHP toolchain is installed

## Run locally

PHP is required. From the project root:

```bash
php -S localhost:8000 -t public
```

Then open <http://localhost:8000>.

## Database foundation

1. Create a MySQL database named `lokal_culture`.
2. Import `database/schema.sql`.
3. Copy `.env.example` to `.env` and fill in the database values.
4. Add PDO connection bootstrapping in `config/database.php` before enabling catalog persistence.

## Payment setup

Copy `.env.example` to `.env` and add Razorpay test credentials:

```text
RAZORPAY_KEY_ID=your_test_key_id
RAZORPAY_KEY_SECRET=your_test_key_secret
```

Checkout creates a pending local order, opens Razorpay Checkout, verifies the returned signature on the server, then marks the order paid and clears the cart. Never commit `.env` or expose `RAZORPAY_KEY_SECRET` to browser JavaScript.

Administrator refunds use the captured Razorpay payment ID and call Razorpay's refund API. The local payment and order are marked refunded only after Razorpay accepts the refund.

## Vendor payouts

Vendor commission is captured on each order item using the vendor's `commission_rate`. Earnings include only paid, processing, shipped, and delivered orders. Cancelled and refunded orders are excluded. Vendors save only masked bank account details, request eligible payouts, and administrators approve, reject, or mark them paid.

The homepage currently uses presentation data so the visual foundation can be reviewed before database-backed catalog work begins.

## Next implementation stages

1. Install PHP 8.2+, Composer, and Laravel or keep the current framework-light structure.
2. Add authentication and policy checks for the three roles.
3. Add admin-managed categories, products, collections, sliders, and vendors.
4. Add customer catalog, cart, wishlist, checkout, and order flow.
5. Add vendor inventory, order processing, commission, membership, and payout workflows.
6. Add payment webhooks, invoices, email, WhatsApp, maps, SEO management, and analytics.