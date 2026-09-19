<?php

declare(strict_types=1);

final class VendorRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function all(): array
    {
        return $this->connection->query(
            'SELECT v.id, v.user_id, v.shop_name, v.slug, v.description, v.state, v.region, v.craft_specialty, v.gstin, v.gst_status, v.gst_document_path, v.registered_address, v.postal_code, v.logo_path, v.verification_status, v.commission_rate, v.is_active, v.created_at, v.updated_at, u.name AS contact_name, u.email, COUNT(DISTINCT p.id) AS product_count
             FROM vendors v INNER JOIN users u ON u.id = v.user_id
             LEFT JOIN products p ON p.vendor_id = v.id
             GROUP BY v.id, u.name, u.email ORDER BY v.created_at DESC'
        )->fetchAll();
    }

    public function find(int $vendorId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT v.id, v.user_id, v.shop_name, v.slug, v.description, v.state, v.region, v.craft_specialty, v.gstin, v.gst_status, v.gst_document_path, v.registered_address, v.postal_code, v.logo_path, v.verification_status, v.commission_rate, v.is_active, v.created_at, v.updated_at, u.name AS contact_name, u.email, COUNT(DISTINCT p.id) AS product_count
             FROM vendors v INNER JOIN users u ON u.id = v.user_id
             LEFT JOIN products p ON p.vendor_id = v.id
             WHERE v.id = :id GROUP BY v.id, u.name, u.email LIMIT 1'
        );
        $statement->execute(['id' => $vendorId]);
        return $statement->fetch() ?: null;
    }

    public function setVerification(int $vendorId, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Unsupported vendor verification status.');
        }
        $statement = $this->connection->prepare(
            "UPDATE vendors SET verification_status = :status, is_active = IF(:status = 'approved', 1, 0) WHERE id = :id"
        );
        $statement->execute(['status' => $status, 'id' => $vendorId]);
    }

    public function setActive(int $vendorId, bool $active): void
    {
        $statement = $this->connection->prepare(
            "UPDATE vendors SET is_active = :is_active WHERE id = :id AND verification_status = 'approved'"
        );
        $statement->execute(['is_active' => $active ? 1 : 0, 'id' => $vendorId]);
    }

    public function updateCommission(int $vendorId, float $rate): void
    {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('Commission must be between 0 and 100 percent.');
        }
        $statement = $this->connection->prepare('UPDATE vendors SET commission_rate = :rate WHERE id = :id');
        $statement->execute(['rate' => $rate, 'id' => $vendorId]);
    }

    public function summary(): array
    {
        return $this->connection->query(
            "SELECT COUNT(*) AS vendors,
                    SUM(verification_status = 'pending') AS pending_vendors,
                    SUM(verification_status = 'approved' AND is_active = 1) AS active_vendors
             FROM vendors"
        )->fetch() ?: ['vendors' => 0, 'pending_vendors' => 0, 'active_vendors' => 0];
    }

    public function dashboardSummary(): array
    {
        $summary = $this->summary();
        $summary['products'] = (int) $this->connection->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $summary['categories'] = (int) $this->connection->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
        $summary['orders'] = (int) $this->connection->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $summary['sales'] = (float) $this->connection->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE status IN ('paid', 'processing', 'shipped', 'delivered')")->fetchColumn();
        $summary['gst'] = (float) $this->connection->query("SELECT COALESCE(SUM(tax_total), 0) FROM orders WHERE status IN ('paid', 'processing', 'shipped', 'delivered')")->fetchColumn();
        $summary['commission'] = (float) $this->connection->query("SELECT COALESCE(SUM(commission_total), 0) FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE o.status IN ('paid', 'processing', 'shipped', 'delivered')")->fetchColumn();
        return $summary;
    }
}