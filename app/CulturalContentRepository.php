<?php

declare(strict_types=1);

final class CulturalContentRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function approved(?int $limit = null): array
    {
        $suffix = $limit === null ? '' : ' LIMIT ' . max(1, min(50, $limit));
        $statement = $this->connection->query("SELECT id, title, slug, excerpt, body, media_type, media_path, region, craft, created_at FROM cultural_content WHERE status = 'approved' ORDER BY approved_at DESC, created_at DESC" . $suffix);
        return $statement->fetchAll();
    }

    public function findApproved(string $slug): ?array
    {
        $statement = $this->connection->prepare("SELECT * FROM cultural_content WHERE slug = :slug AND status = 'approved' LIMIT 1");
        $statement->execute(['slug' => $slug]);
        return $statement->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->connection->query('SELECT c.*, u.name AS author FROM cultural_content c INNER JOIN users u ON u.id = c.created_by ORDER BY c.created_at DESC')->fetchAll();
    }

    public function create(int $userId, array $data): void
    {
        $statement = $this->connection->prepare("INSERT INTO cultural_content (title, slug, excerpt, body, media_type, media_path, region, craft, created_by) VALUES (:title, :slug, :excerpt, :body, :media_type, :media_path, :region, :craft, :created_by)");
        $statement->execute($data + ['created_by' => $userId]);
    }

    public function moderate(int $contentId, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Unsupported content status.');
        }
        $statement = $this->connection->prepare('UPDATE cultural_content SET status = :status, approved_at = IF(:status = \'approved\', CURRENT_TIMESTAMP, NULL) WHERE id = :id AND status = \'pending\'');
        $statement->execute(['status' => $status, 'id' => $contentId]);
    }
}
