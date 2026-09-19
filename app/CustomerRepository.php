<?php

declare(strict_types=1);

final class CustomerRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function updateProfile(int $userId, string $name, string $phone): void
    {
        $statement = $this->connection->prepare('UPDATE users SET name = :name, phone = :phone WHERE id = :id');
        $statement->execute(['name' => $name, 'phone' => $phone, 'id' => $userId]);
    }

    public function addresses(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function saveAddress(int $userId, array $address): void
    {
        $address['is_default'] = !empty($address['is_default']) ? 1 : 0;
        $this->connection->beginTransaction();
        try {
            if ($address['is_default']) {
                $clear = $this->connection->prepare('UPDATE user_addresses SET is_default = FALSE WHERE user_id = :user_id');
                $clear->execute(['user_id' => $userId]);
            }
            $statement = $this->connection->prepare('INSERT INTO user_addresses (user_id, label, recipient_name, address_line1, city, state, postal_code, phone, is_default) VALUES (:user_id, :label, :recipient_name, :address_line1, :city, :state, :postal_code, :phone, :is_default)');
            $statement->execute($address + ['user_id' => $userId]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function deleteAddress(int $userId, int $addressId): void
    {
        $statement = $this->connection->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $addressId, 'user_id' => $userId]);
    }

    public function wishlist(int $userId): array
    {
        $statement = $this->connection->prepare("SELECT p.id, p.name, p.slug, p.price, COALESCE(pi.path, '') AS image FROM wishlists w INNER JOIN products p ON p.id = w.product_id AND p.status = 'published' LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0 WHERE w.user_id = :user_id ORDER BY w.created_at DESC");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function toggleWishlist(int $userId, int $productId): void
    {
        $statement = $this->connection->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (:user_id, :product_id) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $statement->execute(['user_id' => $userId, 'product_id' => $productId]);
        if ($statement->rowCount() === 0) {
            $delete = $this->connection->prepare('DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id');
            $delete->execute(['user_id' => $userId, 'product_id' => $productId]);
        }
    }
}