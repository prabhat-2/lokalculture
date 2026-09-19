<?php

declare(strict_types=1);

final class HomepageRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function collections(): array
    {
        $statement = $this->connection->query(
            "SELECT p.name, p.slug, c.name AS category, p.price, pi.path AS image
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
             WHERE p.status = 'published'
             ORDER BY p.created_at DESC
             LIMIT 2"
        );

        return array_map(static function (array $product): array {
            $product['price'] = '₹' . number_format((float) $product['price'], 0);
            return $product;
        }, $statement->fetchAll());
    }

    public function categories(): array
    {
        $statement = $this->connection->query(
            'SELECT name, slug FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY id LIMIT 4'
        );

        return $statement->fetchAll();
    }

    public function vendors(): array
    {
        $statement = $this->connection->query(
            "SELECT v.shop_name AS name, v.description AS type,
                    COALESCE(MIN(pi.path), '') AS image
             FROM vendors v
             INNER JOIN products p ON p.vendor_id = v.id AND p.status = 'published'
             LEFT JOIN product_images pi ON pi.product_id = p.id
             WHERE v.verification_status = 'approved' AND v.is_active = 1
             GROUP BY v.id, v.shop_name, v.description
             ORDER BY v.created_at DESC
             LIMIT 3"
        );

        return $statement->fetchAll();
    }

    public function directoryVendors(): array
    {
        $statement = $this->connection->query(
            "SELECT v.shop_name AS name, v.slug, v.description AS type,
                    COALESCE(MIN(pi.path), '') AS image
             FROM vendors v
             INNER JOIN products p ON p.vendor_id = v.id AND p.status = 'published'
             LEFT JOIN product_images pi ON pi.product_id = p.id
             WHERE v.verification_status = 'approved' AND v.is_active = 1
             GROUP BY v.id, v.shop_name, v.slug, v.description
             ORDER BY v.created_at DESC"
        );
        return $statement->fetchAll();
    }
}