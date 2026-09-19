<?php

declare(strict_types=1);

final class OrderRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function forCustomer(int $customerId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, order_number, status, grand_total, created_at FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC'
        );
        $statement->execute(['customer_id' => $customerId]);
        return $statement->fetchAll();
    }

    public function forVendor(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT DISTINCT o.id, o.order_number, o.status, o.grand_total, o.created_at, u.name AS customer
             FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id
             INNER JOIN vendors v ON v.id = oi.vendor_id AND v.user_id = :user_id
             INNER JOIN users u ON u.id = o.customer_id ORDER BY o.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function all(): array
    {
        return $this->connection->query(
            'SELECT o.id, o.order_number, o.status, o.grand_total, o.created_at, u.name AS customer, u.email FROM orders o INNER JOIN users u ON u.id = o.customer_id ORDER BY o.created_at DESC'
        )->fetchAll();
    }

    public function details(string $orderNumber, ?int $customerId = null, ?int $vendorUserId = null): ?array
    {
        $conditions = ['o.order_number = :order_number'];
        $parameters = ['order_number' => $orderNumber];
        if ($customerId !== null) {
            $conditions[] = 'o.customer_id = :customer_id';
            $parameters['customer_id'] = $customerId;
        }
        if ($vendorUserId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM order_items own_oi INNER JOIN vendors own_v ON own_v.id = own_oi.vendor_id WHERE own_oi.order_id = o.id AND own_v.user_id = :vendor_user_id)';
            $parameters['vendor_user_id'] = $vendorUserId;
        }
        $statement = $this->connection->prepare(
            'SELECT o.*, u.name AS customer, u.email, a.recipient_name, a.address_line1, a.city, a.state, a.postal_code, a.phone, s.carrier, s.tracking_number, s.status AS shipment_status
             FROM orders o INNER JOIN users u ON u.id = o.customer_id LEFT JOIN order_addresses a ON a.order_id = o.id LEFT JOIN shipments s ON s.order_id = o.id
             WHERE ' . implode(' AND ', $conditions) . ' LIMIT 1'
        );
        $statement->execute($parameters);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $items = $this->connection->prepare(
            'SELECT oi.*, v.shop_name AS vendor FROM order_items oi INNER JOIN vendors v ON v.id = oi.vendor_id WHERE oi.order_id = :order_id ORDER BY oi.id'
        );
        $items->execute(['order_id' => $order['id']]);
        $order['items'] = $items->fetchAll();
        $shipments = $this->connection->prepare('SELECT s.*, v.shop_name AS vendor FROM shipments s LEFT JOIN vendors v ON v.id = s.vendor_id WHERE s.order_id = :order_id ORDER BY s.id');
        $shipments->execute(['order_id' => $order['id']]);
        $order['shipments'] = $shipments->fetchAll();
        return $order;
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $allowed = ['processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported order status.');
        }
        $statement = $this->connection->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $orderId]);
        if ($status === 'cancelled' && $statement->rowCount() === 1) {
            $this->restoreInventory($orderId);
        }
        if (in_array($status, ['shipped', 'delivered'], true)) {
            $shipmentStatus = $status === 'delivered' ? 'delivered' : 'shipped';
            $shipment = $this->connection->prepare("INSERT INTO shipments (order_id, status, shipped_at, delivered_at) VALUES (:order_id, :status, IF(:is_shipped = 1, CURRENT_TIMESTAMP, NULL), IF(:is_delivered = 1, CURRENT_TIMESTAMP, NULL)) ON DUPLICATE KEY UPDATE status = VALUES(status), shipped_at = COALESCE(shipments.shipped_at, VALUES(shipped_at)), delivered_at = VALUES(delivered_at)");
            $shipment->execute(['order_id' => $orderId, 'status' => $shipmentStatus, 'is_shipped' => 1, 'is_delivered' => $status === 'delivered' ? 1 : 0]);
        }
    }

    public function cancelForCustomer(int $customerId, string $orderNumber): void
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare("UPDATE orders SET status = 'cancelled' WHERE customer_id = :customer_id AND order_number = :order_number AND status IN ('pending', 'paid')");
            $statement->execute(['customer_id' => $customerId, 'order_number' => $orderNumber]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('This order can no longer be cancelled.');
            }
            $order = $this->connection->prepare('SELECT id FROM orders WHERE customer_id = :customer_id AND order_number = :order_number LIMIT 1');
            $order->execute(['customer_id' => $customerId, 'order_number' => $orderNumber]);
            $this->restoreInventory((int) $order->fetchColumn());
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function expirePendingOrders(int $minutes = 60): int
    {
        $this->connection->beginTransaction();
        try {
            $minutes = max(1, min(10080, $minutes));
            $orders = $this->connection->prepare("SELECT id FROM orders WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE) FOR UPDATE");
            $orders->execute();
            $expired = $orders->fetchAll(PDO::FETCH_COLUMN);
            if ($expired) {
                $restore = $this->connection->prepare('UPDATE inventory i INNER JOIN order_items oi ON oi.product_id = i.product_id SET i.available_quantity = i.available_quantity + oi.quantity, i.reserved_quantity = GREATEST(i.reserved_quantity - oi.quantity, 0) WHERE oi.order_id = :order_id');
                $cancel = $this->connection->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id AND status = 'pending'");
                foreach ($expired as $orderId) {
                    $restore->execute(['order_id' => $orderId]);
                    $cancel->execute(['id' => $orderId]);
                }
            }
            $this->connection->commit();
            return count($expired);
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function requestReturn(int $customerId, string $orderNumber, string $reason): void
    {
        $statement = $this->connection->prepare("SELECT id, grand_total FROM orders WHERE customer_id = :customer_id AND order_number = :order_number AND status IN ('paid', 'processing', 'shipped', 'delivered') LIMIT 1");
        $statement->execute(['customer_id' => $customerId, 'order_number' => $orderNumber]);
        $order = $statement->fetch();
        if (!$order || trim($reason) === '') {
            throw new RuntimeException('This order is not eligible for a return request.');
        }
        $insert = $this->connection->prepare('INSERT INTO returns (order_id, customer_id, reason, refund_amount) VALUES (:order_id, :customer_id, :reason, :refund_amount)');
        try {
            $insert->execute(['order_id' => $order['id'], 'customer_id' => $customerId, 'reason' => trim($reason), 'refund_amount' => $order['grand_total']]);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('A return request already exists for this order.');
            }
            throw $exception;
        }
    }

    public function reviewReturn(int $returnId, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Unsupported return status.');
        }
        $statement = $this->connection->prepare("UPDATE returns SET status = :status, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'requested'");
        $statement->execute(['status' => $status, 'id' => $returnId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('This return request has already been reviewed.');
        }
    }

    public function markReturnRefunded(int $orderId): void
    {
        $statement = $this->connection->prepare("UPDATE returns SET status = 'refunded', reviewed_at = COALESCE(reviewed_at, CURRENT_TIMESTAMP) WHERE order_id = :order_id AND status = 'approved'");
        $statement->execute(['order_id' => $orderId]);
    }

    private function restoreInventory(int $orderId): void
    {
        $items = $this->connection->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :order_id');
        $items->execute(['order_id' => $orderId]);
        $restore = $this->connection->prepare('UPDATE inventory SET available_quantity = available_quantity + :available, reserved_quantity = GREATEST(reserved_quantity - :reserved, 0) WHERE product_id = :product_id');
        foreach ($items->fetchAll() as $item) {
            $restore->execute(['available' => $item['quantity'], 'reserved' => $item['quantity'], 'product_id' => $item['product_id']]);
        }
    }
}