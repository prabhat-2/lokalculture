<?php

declare(strict_types=1);

final class LogisticsService
{
    public function __construct(private PDO $connection)
    {
    }

    public function prepareVendorShipments(int $orderId): void
    {
        $order = $this->connection->prepare('SELECT a.recipient_name, a.address_line1, a.city, a.state, a.postal_code, a.phone FROM order_addresses a WHERE a.order_id = :order_id LIMIT 1');
        $order->execute(['order_id' => $orderId]);
        $delivery = $order->fetch();
        if (!$delivery) {
            throw new RuntimeException('Order address is missing.');
        }
        $deliveryAddress = implode(', ', [$delivery['recipient_name'], $delivery['address_line1'], $delivery['city'], $delivery['state'], $delivery['postal_code'], $delivery['phone']]);
        $vendors = $this->connection->prepare('SELECT DISTINCT oi.vendor_id, v.registered_address, v.state, v.postal_code FROM order_items oi INNER JOIN vendors v ON v.id = oi.vendor_id WHERE oi.order_id = :order_id');
        $vendors->execute(['order_id' => $orderId]);
        $shipment = $this->connection->prepare("INSERT INTO shipments (order_id, vendor_id, pickup_address, delivery_address, status) VALUES (:order_id, :vendor_id, :pickup_address, :delivery_address, 'not_shipped') ON DUPLICATE KEY UPDATE pickup_address = VALUES(pickup_address), delivery_address = VALUES(delivery_address)");
        foreach ($vendors->fetchAll() as $vendor) {
            $pickup = trim((string) ($vendor['registered_address'] ?? '')) . ', ' . trim((string) ($vendor['state'] ?? '')) . ' ' . trim((string) ($vendor['postal_code'] ?? ''));
            $shipment->execute(['order_id' => $orderId, 'vendor_id' => $vendor['vendor_id'], 'pickup_address' => trim($pickup, ' ,'), 'delivery_address' => $deliveryAddress]);
        }
    }
}