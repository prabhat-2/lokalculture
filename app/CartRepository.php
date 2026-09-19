<?php

declare(strict_types=1);

final class CartRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function countItems(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(ci.quantity), 0) FROM carts c
             INNER JOIN cart_items ci ON ci.cart_id = c.id WHERE c.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function items(int $userId): array
    {
        $statement = $this->connection->prepare(
            "SELECT ci.product_id, ci.quantity, p.name, p.slug, p.price, v.id AS vendor_id,
                  v.shop_name AS vendor, v.state AS vendor_state, v.commission_rate,
                  h.hsn_code, h.gst_rate, COALESCE(pi.path, '') AS image,
                    COALESCE(i.available_quantity, 0) AS stock
             FROM carts c INNER JOIN cart_items ci ON ci.cart_id = c.id
             INNER JOIN products p ON p.id = ci.product_id AND p.status = 'published'
             INNER JOIN vendors v ON v.id = p.vendor_id
              INNER JOIN hsn_codes h ON h.id = p.hsn_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
             LEFT JOIN inventory i ON i.product_id = p.id
             WHERE c.user_id = :user_id ORDER BY ci.created_at DESC"
        );
        $statement->execute(['user_id' => $userId]);
        $items = $statement->fetchAll();
        foreach ($items as &$item) {
            $item['price'] = (float) $item['price'];
            $item['line_total'] = $item['price'] * (int) $item['quantity'];
        }
        return $items;
    }

    public function add(int $userId, int $productId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }
        $this->connection->beginTransaction();
        try {
            $cartId = $this->cartId($userId);
            $product = $this->productForCart($productId);
            if (!$product || (int) $product['stock'] < $quantity) {
                throw new RuntimeException('This product does not have enough stock.');
            }
            $statement = $this->connection->prepare(
                'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)
                 ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), :stock)'
            );
            $statement->execute(['cart_id' => $cartId, 'product_id' => $productId, 'quantity' => $quantity, 'stock' => (int) $product['stock']]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function update(int $userId, int $productId, int $quantity): void
    {
        $cartId = $this->cartId($userId);
        if ($quantity <= 0) {
            $statement = $this->connection->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id');
            $statement->execute(['cart_id' => $cartId, 'product_id' => $productId]);
            return;
        }
        $product = $this->productForCart($productId);
        if (!$product || $quantity > (int) $product['stock']) {
            throw new RuntimeException('Requested quantity is not available.');
        }
        $statement = $this->connection->prepare('UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cart_id AND product_id = :product_id');
        $statement->execute(['quantity' => $quantity, 'cart_id' => $cartId, 'product_id' => $productId]);
    }

    public function checkout(int $userId, array $address): array
    {
        $this->connection->beginTransaction();
        try {
            $items = $this->items($userId);
            if (!$items) {
                throw new RuntimeException('Your cart is empty.');
            }
            foreach ($items as $item) {
                if ((int) $item['quantity'] > (int) $item['stock']) {
                    throw new RuntimeException('Stock changed for ' . $item['name'] . '. Please review your cart.');
                }
                if (trim((string) $item['vendor_state']) === '') {
                    throw new RuntimeException('The vendor state is not configured for ' . $item['name'] . '.');
                }
            }
            $subtotal = array_sum(array_column($items, 'line_total'));
            $customerState = trim((string) ($address['state'] ?? ''));
            $taxTotal = 0.0;
            $taxedItems = [];
            foreach ($items as $item) {
                $taxableAmount = round((float) $item['line_total'], 2);
                $gstRate = (float) $item['gst_rate'];
                $gstAmount = round($taxableAmount * $gstRate / 100, 2);
                $sameState = strcasecmp(trim((string) $item['vendor_state']), $customerState) === 0;
                $cgstRate = $sameState ? round($gstRate / 2, 2) : 0.0;
                $sgstRate = $sameState ? round($gstRate - $cgstRate, 2) : 0.0;
                $igstRate = $sameState ? 0.0 : $gstRate;
                $cgstAmount = $sameState ? round($gstAmount / 2, 2) : 0.0;
                $sgstAmount = $sameState ? round($gstAmount - $cgstAmount, 2) : 0.0;
                $igstAmount = $sameState ? 0.0 : $gstAmount;
                $taxedItems[] = $item + [
                    'taxable_amount' => $taxableAmount,
                    'cgst_rate' => $cgstRate,
                    'cgst_amount' => $cgstAmount,
                    'sgst_rate' => $sgstRate,
                    'sgst_amount' => $sgstAmount,
                    'igst_rate' => $igstRate,
                    'igst_amount' => $igstAmount,
                    'gst_amount' => $gstAmount,
                ];
                $taxTotal += $gstAmount;
            }
            $grandTotal = round($subtotal + $taxTotal, 2);
            $orderNumber = 'LC-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $order = $this->connection->prepare(
                "INSERT INTO orders (customer_id, order_number, status, subtotal, tax_total, grand_total)
                 VALUES (:customer_id, :order_number, 'pending', :subtotal, :tax_total, :grand_total)"
            );
            $order->execute(['customer_id' => $userId, 'order_number' => $orderNumber, 'subtotal' => $subtotal, 'tax_total' => $taxTotal, 'grand_total' => $grandTotal]);
            $orderId = (int) $this->connection->lastInsertId();
            $addressStatement = $this->connection->prepare(
                'INSERT INTO order_addresses (order_id, recipient_name, address_line1, city, state, postal_code, phone) VALUES (:order_id, :recipient_name, :address_line1, :city, :state, :postal_code, :phone)'
            );
            $addressStatement->execute($address + ['order_id' => $orderId]);
            $itemStatement = $this->connection->prepare(
                'INSERT INTO order_items (order_id, vendor_id, product_id, product_name, hsn_code, gst_rate, quantity, unit_price, taxable_amount, cgst_rate, cgst_amount, sgst_rate, sgst_amount, igst_rate, igst_amount, gst_amount, commission_total)
                 VALUES (:order_id, :vendor_id, :product_id, :product_name, :hsn_code, :gst_rate, :quantity, :unit_price, :taxable_amount, :cgst_rate, :cgst_amount, :sgst_rate, :sgst_amount, :igst_rate, :igst_amount, :gst_amount, :commission_total)'
            );
            $stockStatement = $this->connection->prepare('UPDATE inventory SET available_quantity = available_quantity - :decrement, reserved_quantity = reserved_quantity + :reserve WHERE product_id = :product_id AND available_quantity >= :available');
            foreach ($taxedItems as $item) {
                $commission = round(((float) $item['price'] * (int) $item['quantity']) * ((float) $item['commission_rate'] / 100), 2);
                $itemStatement->execute([
                    'order_id' => $orderId,
                    'vendor_id' => $item['vendor_id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'hsn_code' => $item['hsn_code'],
                    'gst_rate' => $item['gst_rate'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'taxable_amount' => $item['taxable_amount'],
                    'cgst_rate' => $item['cgst_rate'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_rate' => $item['sgst_rate'],
                    'sgst_amount' => $item['sgst_amount'],
                    'igst_rate' => $item['igst_rate'],
                    'igst_amount' => $item['igst_amount'],
                    'gst_amount' => $item['gst_amount'],
                    'commission_total' => $commission,
                ]);
                $stockStatement->execute(['decrement' => $item['quantity'], 'reserve' => $item['quantity'], 'available' => $item['quantity'], 'product_id' => $item['product_id']]);
                if ($stockStatement->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while creating the order.');
                }
            }
            $this->connection->commit();
            return ['id' => $orderId, 'number' => $orderNumber, 'amount' => $grandTotal];
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function cancelPendingOrder(int $orderId): void
    {
        $this->connection->beginTransaction();
        try {
            $items = $this->connection->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :order_id');
            $items->execute(['order_id' => $orderId]);
            $restore = $this->connection->prepare('UPDATE inventory SET available_quantity = available_quantity + :available, reserved_quantity = reserved_quantity - :reserved WHERE product_id = :product_id');
            foreach ($items->fetchAll() as $item) {
                $restore->execute(['available' => $item['quantity'], 'reserved' => $item['quantity'], 'product_id' => $item['product_id']]);
            }
            $delete = $this->connection->prepare('DELETE FROM orders WHERE id = :order_id AND status = \'pending\'');
            $delete->execute(['order_id' => $orderId]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function cartId(int $userId): int
    {
        $insert = $this->connection->prepare('INSERT INTO carts (user_id) VALUES (:user_id) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $insert->execute(['user_id' => $userId]);
        return (int) $this->connection->lastInsertId();
    }

    private function productForCart(int $productId): ?array
    {
        $statement = $this->connection->prepare("SELECT p.id, COALESCE(i.available_quantity, 0) AS stock FROM products p LEFT JOIN inventory i ON i.product_id = p.id WHERE p.id = :id AND p.status = 'published' LIMIT 1");
        $statement->execute(['id' => $productId]);
        return $statement->fetch() ?: null;
    }
}