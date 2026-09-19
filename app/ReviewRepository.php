<?php

declare(strict_types=1);

final class ReviewRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function forProduct(int $productId): array
    {
        $statement = $this->connection->prepare(
            'SELECT r.rating, r.comment, r.created_at, u.name AS reviewer
             FROM product_reviews r INNER JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :product_id ORDER BY r.created_at DESC'
        );
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }

    public function summary(int $productId): array
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS average_rating
             FROM product_reviews WHERE product_id = :product_id'
        );
        $statement->execute(['product_id' => $productId]);
        $row = $statement->fetch() ?: ['review_count' => 0, 'average_rating' => 0];
        return [
            'review_count' => (int) $row['review_count'],
            'average_rating' => round((float) $row['average_rating'], 1),
        ];
    }

    public function hasReviewed(int $productId, int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM product_reviews WHERE product_id = :product_id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['product_id' => $productId, 'user_id' => $userId]);
        return (bool) $statement->fetchColumn();
    }

    public function add(int $productId, int $userId, int $rating, string $comment): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }
        $statement = $this->connection->prepare(
            'INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (:product_id, :user_id, :rating, :comment)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)'
        );
        $statement->execute(['product_id' => $productId, 'user_id' => $userId, 'rating' => $rating, 'comment' => $comment !== '' ? $comment : null]);
    }
}
