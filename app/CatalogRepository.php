<?php

declare(strict_types=1);

final class CatalogRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function suggest(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $statement = $this->connection->prepare(
            "SELECT p.name, p.slug, c.name AS category
             FROM products p INNER JOIN categories c ON c.id = p.category_id AND c.is_active = 1
             INNER JOIN vendors v ON v.id = p.vendor_id AND v.verification_status = 'approved' AND v.is_active = 1
             WHERE p.status = 'published' AND p.name LIKE :term
             ORDER BY p.created_at DESC LIMIT 6"
        );
        $statement->execute(['term' => '%' . $term . '%']);
        return $statement->fetchAll();
    }

    public function categories(): array
    {
        return $this->connection->query(
            'SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY id'
        )->fetchAll();
    }
    public function adminCategories(): array
    {
        return $this->connection->query(
            'SELECT id, parent_id, name, slug, image_path, is_active FROM categories ORDER BY name'
        )->fetchAll();
    }

    public function createCategory(string $name, string $slug, ?int $parentId = null): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO categories (parent_id, name, slug, is_active) VALUES (:parent_id, :name, :slug, 1)'
        );
        $statement->execute(['parent_id' => $parentId, 'name' => $name, 'slug' => $slug]);
    }

    public function updateCategory(int $categoryId, string $name, string $slug, ?int $parentId = null): void
    {
        $statement = $this->connection->prepare(
            'UPDATE categories SET parent_id = :parent_id, name = :name, slug = :slug WHERE id = :id'
        );
        $statement->execute(['parent_id' => $parentId, 'name' => $name, 'slug' => $slug, 'id' => $categoryId]);
    }

    public function archiveCategory(int $categoryId): void
    {
        $statement = $this->connection->prepare('UPDATE categories SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $categoryId]);
    }
    public function activeHsnCodes(): array
    {
        return $this->connection->query(
            'SELECT id, hsn_code, description, gst_rate FROM hsn_codes WHERE is_active = 1 ORDER BY hsn_code'
        )->fetchAll();
    }

    public function activeHsnCode(int $hsnId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, hsn_code, gst_rate FROM hsn_codes WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $hsnId]);
        return $statement->fetch() ?: null;
    }

    public function products(?string $search = null, ?string $category = null, ?string $region = null, ?string $craft = null, ?string $sort = null, int $page = 1, int $perPage = 12): array
    {
        $conditions = ["p.status = 'published'"];
        $parameters = [];
        if ($search !== null && trim($search) !== '') {
            $conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
            $parameters['search'] = '%' . trim($search) . '%';
        }
        if ($category !== null && trim($category) !== '') {
            $conditions[] = 'c.slug = :category';
            $parameters['category'] = trim($category);
        }
        if ($region !== null && trim($region) !== '') {
            $conditions[] = 'v.region = :region';
            $parameters['region'] = trim($region);
        }
        if ($craft !== null && trim($craft) !== '') {
            $conditions[] = 'v.craft_specialty = :craft';
            $parameters['craft'] = trim($craft);
        }
        $page = max(1, $page);
        $perPage = min(48, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $orderBy = match ($sort) {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.created_at DESC',
        };

        $statement = $this->connection->prepare(
            'SELECT p.name, p.slug, p.price, p.description, c.name AS category, c.slug AS category_slug,
                    v.shop_name AS vendor, v.slug AS vendor_slug, COALESCE(pi.path, \'\') AS image,
                    COALESCE(i.available_quantity, 0) AS stock
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id AND c.is_active = 1
             INNER JOIN vendors v ON v.id = p.vendor_id AND v.verification_status = \'approved\' AND v.is_active = 1
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
             LEFT JOIN inventory i ON i.product_id = p.id
               WHERE ' . implode(' AND ', $conditions) . ' ORDER BY ' . $orderBy . ' LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute($parameters);
        return $this->formatProducts($statement->fetchAll());
    }

    public function productCount(?string $search = null, ?string $category = null, ?string $region = null, ?string $craft = null): int
    {
        $conditions = ["p.status = 'published'"];
        $parameters = [];
        foreach ([['value' => $search, 'sql' => '(p.name LIKE :search OR p.description LIKE :search)', 'key' => 'search'], ['value' => $category, 'sql' => 'c.slug = :category', 'key' => 'category'], ['value' => $region, 'sql' => 'v.region = :region', 'key' => 'region'], ['value' => $craft, 'sql' => 'v.craft_specialty = :craft', 'key' => 'craft']] as $filter) {
            if ($filter['value'] !== null && trim($filter['value']) !== '') {
                $conditions[] = $filter['sql'];
                $parameters[$filter['key']] = $filter['key'] === 'search' ? '%' . trim($filter['value']) . '%' : trim($filter['value']);
            }
        }
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM products p INNER JOIN categories c ON c.id = p.category_id AND c.is_active = 1 INNER JOIN vendors v ON v.id = p.vendor_id AND v.verification_status = \'approved\' AND v.is_active = 1 WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function filterOptions(): array
    {
        return [
            'regions' => $this->connection->query("SELECT DISTINCT region AS value FROM vendors WHERE region IS NOT NULL AND region <> '' AND verification_status = 'approved' AND is_active = 1 ORDER BY region")->fetchAll(),
            'crafts' => $this->connection->query("SELECT DISTINCT craft_specialty AS value FROM vendors WHERE craft_specialty IS NOT NULL AND craft_specialty <> '' AND verification_status = 'approved' AND is_active = 1 ORDER BY craft_specialty")->fetchAll(),
        ];
    }

    public function product(string $slug): ?array
    {
        $statement = $this->connection->prepare(
                "SELECT p.id, p.name, p.slug, p.price, p.description, p.sku, c.name AS category,
                    v.shop_name AS vendor, v.slug AS vendor_slug, v.region, v.craft_specialty,
                    h.hsn_code, h.gst_rate, COALESCE(i.available_quantity, 0) AS stock
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id AND c.is_active = 1
             INNER JOIN vendors v ON v.id = p.vendor_id AND v.verification_status = 'approved' AND v.is_active = 1
                 INNER JOIN hsn_codes h ON h.id = p.hsn_id
             LEFT JOIN inventory i ON i.product_id = p.id
             WHERE p.slug = :slug AND p.status = 'published' LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $product = $statement->fetch();
        if (!$product) {
            return null;
        }

        $images = $this->connection->prepare('SELECT path FROM product_images WHERE product_id = (SELECT id FROM products WHERE slug = :slug) ORDER BY sort_order');
        $images->execute(['slug' => $slug]);
        $product['images'] = array_column($images->fetchAll(), 'path');
        $product['price'] = '₹' . number_format((float) $product['price'], 0);
        return $product;
    }

    public function relatedProducts(int $productId): array
    {
        $statement = $this->connection->prepare(
            "SELECT p.name, p.slug, p.price, c.name AS category, COALESCE(pi.path, '') AS image
             FROM products p INNER JOIN categories c ON c.id = p.category_id AND c.is_active = 1
             INNER JOIN vendors v ON v.id = p.vendor_id AND v.verification_status = 'approved' AND v.is_active = 1
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
               WHERE p.status = 'published' AND p.category_id = (SELECT category_id FROM products WHERE id = :category_product_id) AND p.id <> :excluded_product_id
             ORDER BY p.created_at DESC LIMIT 4"
        );
           $statement->execute(['category_product_id' => $productId, 'excluded_product_id' => $productId]);
        return $this->formatProducts($statement->fetchAll());
    }

    public function vendor(string $slug): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT id, shop_name AS name, slug, description, region, craft_specialty FROM vendors WHERE slug = :slug AND verification_status = 'approved' AND is_active = 1 LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $vendor = $statement->fetch();
        if (!$vendor) {
            return null;
        }
        $vendor['products'] = $this->productsForVendor((int) $vendor['id']);
        return $vendor;
    }

    public function productsForVendor(int $vendorId): array
    {
        $statement = $this->connection->prepare(
            "SELECT p.name, p.slug, p.price, p.status, c.name AS category, COALESCE(pi.path, '') AS image
             FROM products p INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
             WHERE p.vendor_id = :vendor_id AND p.status = 'published' ORDER BY p.created_at DESC"
        );
        $statement->execute(['vendor_id' => $vendorId]);
        return $this->formatProducts($statement->fetchAll());
    }

    public function lowStockForVendor(int $vendorId, int $threshold = 5): array
    {
        $statement = $this->connection->prepare(
            "SELECT p.name, p.slug, COALESCE(i.available_quantity, 0) AS stock
             FROM products p LEFT JOIN inventory i ON i.product_id = p.id
             WHERE p.vendor_id = :vendor_id AND p.status = 'published' AND COALESCE(i.available_quantity, 0) <= :threshold
             ORDER BY stock ASC"
        );
        $statement->execute(['vendor_id' => $vendorId, 'threshold' => $threshold]);
        return $statement->fetchAll();
    }

    public function vendorForUser(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, shop_name AS name, slug FROM vendors WHERE user_id = :user_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $vendor = $statement->fetch();
        if (!$vendor) {
            return null;
        }
        $vendor['products'] = $this->productsForVendor((int) $vendor['id']);
        return $vendor;
    }

    public function pendingProducts(): array
    {
        $statement = $this->connection->query(
            "SELECT p.id, p.name, p.slug, p.price, p.description, p.created_at,
                    v.shop_name AS vendor, c.name AS category, COALESCE(pi.path, '') AS image
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
             WHERE p.status = 'pending' ORDER BY p.created_at ASC"
        );
        return $this->formatProducts($statement->fetchAll());
    }

    public function moderateProduct(int $productId, string $status): void
    {
        if (!in_array($status, ['published', 'archived'], true)) {
            throw new InvalidArgumentException('Unsupported moderation status.');
        }
        $statement = $this->connection->prepare(
            "UPDATE products SET status = :status WHERE id = :id AND status = 'pending'"
        );
        $statement->execute(['status' => $status, 'id' => $productId]);
    }

    public function productForVendor(int $vendorId, string $slug): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT p.id, p.name, p.slug, p.sku, p.description, p.price, p.category_id, p.hsn_id, c.name AS category,
                    COALESCE(i.available_quantity, 0) AS stock, COALESCE(pi.path, \'\') AS image
             FROM products p INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN inventory i ON i.product_id = p.id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
             WHERE p.vendor_id = :vendor_id AND p.slug = :slug LIMIT 1'
        );
        $statement->execute(['vendor_id' => $vendorId, 'slug' => $slug]);
        return $statement->fetch() ?: null;
    }

    public function createProduct(int $vendorId, array $data): void
    {
        $this->connection->beginTransaction();
        try {
            $product = $this->connection->prepare(
                "INSERT INTO products (vendor_id, category_id, hsn_id, name, slug, sku, description, price, status)
                 VALUES (:vendor_id, :category_id, :hsn_id, :name, :slug, :sku, :description, :price, 'pending')"
            );
            $product->execute([
                'vendor_id' => $vendorId,
                'category_id' => $data['category_id'],
                'hsn_id' => $data['hsn_id'],
                'name' => $data['name'],
                'slug' => $data['slug'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'price' => $data['price'],
            ]);
            $productId = (int) $this->connection->lastInsertId();
            $this->saveProductAssets($productId, $data['image'], (int) $data['stock']);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function updateProduct(int $vendorId, int $productId, array $data): void
    {
        $this->connection->beginTransaction();
        try {
            $product = $this->connection->prepare(
                'UPDATE products SET category_id = :category_id, hsn_id = :hsn_id, name = :name, sku = :sku, description = :description, price = :price WHERE id = :id AND vendor_id = :vendor_id'
            );
            $product->execute([
                'category_id' => $data['category_id'],
                'hsn_id' => $data['hsn_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'price' => $data['price'],
                'id' => $productId,
                'vendor_id' => $vendorId,
            ]);
            $this->saveProductAssets($productId, $data['image'], (int) $data['stock']);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function saveProductAssets(int $productId, string $image, int $stock): void
    {
        // --- Primary image ---
        // Fetch existing primary-image row(s) for this product.
        $fetch = $this->connection->prepare(
            'SELECT id FROM product_images WHERE product_id = :product_id AND sort_order = 0 ORDER BY id ASC'
        );
        $fetch->execute(['product_id' => $productId]);
        $rows = $fetch->fetchAll();

        if ($rows) {
            // Clean up any duplicates created by the previous rowCount() bug — keep only the oldest row.
            if (count($rows) > 1) {
                $keepId = (int) $rows[0]['id'];
                $this->connection->prepare(
                    'DELETE FROM product_images WHERE product_id = :product_id AND sort_order = 0 AND id != :keep_id'
                )->execute(['product_id' => $productId, 'keep_id' => $keepId]);
            }
            // Only overwrite the stored path when a non-empty image string is supplied.
            // An empty string means "no new upload on an edit" — preserve what is there.
            if ($image !== '') {
                $this->connection->prepare(
                    'UPDATE product_images SET path = :path WHERE id = :id'
                )->execute(['path' => $image, 'id' => (int) $rows[0]['id']]);
            }
        } else {
            // No image record exists yet — insert one (only when we actually have a path).
            if ($image !== '') {
                $this->connection->prepare(
                    'INSERT INTO product_images (product_id, path, sort_order) VALUES (:product_id, :path, 0)'
                )->execute(['product_id' => $productId, 'path' => $image]);
            }
        }

        // --- Inventory ---
        $this->connection->prepare(
            'INSERT INTO inventory (product_id, available_quantity) VALUES (:product_id, :stock)
             ON DUPLICATE KEY UPDATE available_quantity = VALUES(available_quantity)'
        )->execute(['product_id' => $productId, 'stock' => $stock]);
    }

    private function formatProducts(array $products): array
    {
        return array_map(static function (array $product): array {
            $product['price'] = '₹' . number_format((float) $product['price'], 0);
            return $product;
        }, $products);
    }
}