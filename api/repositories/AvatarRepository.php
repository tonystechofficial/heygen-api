<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Avatar Repository - handles database operations for avatar cache
 * Follows Repository Pattern and Single Responsibility Principle
 */
class AvatarRepository
{
    private PDO $db;
    private int $cacheExpiryHours = 24;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all cached avatars
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM avatars_cache ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Find avatar by HeyGen ID
     */
    public function findByHeygenId(string $heygenAvatarId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM avatars_cache WHERE heygen_avatar_id = :id");
        $stmt->execute(['id' => $heygenAvatarId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if cache is still valid
     */
    public function isCacheValid(): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM avatars_cache WHERE cached_at > DATE_SUB(NOW(), INTERVAL :hours HOUR)"
        );
        $stmt->execute(['hours' => $this->cacheExpiryHours]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Clear all cached avatars
     */
    public function clearCache(): bool
    {
        return $this->db->exec("DELETE FROM avatars_cache") !== false;
    }

    /**
     * Upsert avatar (insert or update)
     */
    public function upsert(array $data): bool
    {
        $sql = "INSERT INTO avatars_cache (heygen_avatar_id, name, preview_url, gender, avatar_type, cached_at)
                VALUES (:heygen_avatar_id, :name, :preview_url, :gender, :avatar_type, NOW())
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    preview_url = VALUES(preview_url),
                    gender = VALUES(gender),
                    avatar_type = VALUES(avatar_type),
                    cached_at = NOW()";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'heygen_avatar_id' => $data['heygen_avatar_id'],
            'name' => $data['name'],
            'preview_url' => $data['preview_url'] ?? null,
            'gender' => $data['gender'] ?? null,
            'avatar_type' => $data['avatar_type'] ?? null,
        ]);
    }

    /**
     * Bulk insert avatars
     */
    public function bulkInsert(array $avatars): int
    {
        $count = 0;
        foreach ($avatars as $avatar) {
            if ($this->upsert($avatar)) {
                $count++;
            }
        }
        return $count;
    }
}

