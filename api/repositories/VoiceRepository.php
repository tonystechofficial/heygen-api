<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Voice Repository - handles database operations for voice cache
 * Follows Repository Pattern and Single Responsibility Principle
 */
class VoiceRepository
{
    private PDO $db;
    private int $cacheExpiryHours = 24;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all cached voices
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM voices_cache ORDER BY language, name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Find voices by language
     */
    public function findByLanguage(string $language): array
    {
        $stmt = $this->db->prepare("SELECT * FROM voices_cache WHERE language LIKE :language ORDER BY name ASC");
        $stmt->execute(['language' => $language . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Find voice by HeyGen ID
     */
    public function findByHeygenId(string $heygenVoiceId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM voices_cache WHERE heygen_voice_id = :id");
        $stmt->execute(['id' => $heygenVoiceId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if cache is still valid
     */
    public function isCacheValid(): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM voices_cache WHERE cached_at > DATE_SUB(NOW(), INTERVAL :hours HOUR)"
        );
        $stmt->execute(['hours' => $this->cacheExpiryHours]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Clear all cached voices
     */
    public function clearCache(): bool
    {
        return $this->db->exec("DELETE FROM voices_cache") !== false;
    }

    /**
     * Upsert voice (insert or update)
     */
    public function upsert(array $data): bool
    {
        $sql = "INSERT INTO voices_cache (heygen_voice_id, name, language, gender, preview_url, is_premium, cached_at)
                VALUES (:heygen_voice_id, :name, :language, :gender, :preview_url, :is_premium, NOW())
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    language = VALUES(language),
                    gender = VALUES(gender),
                    preview_url = VALUES(preview_url),
                    is_premium = VALUES(is_premium),
                    cached_at = NOW()";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'heygen_voice_id' => $data['heygen_voice_id'],
            'name' => $data['name'],
            'language' => $data['language'] ?? null,
            'gender' => $data['gender'] ?? null,
            'preview_url' => $data['preview_url'] ?? null,
            'is_premium' => $data['is_premium'] ?? 0,
        ]);
    }

    /**
     * Bulk insert voices
     */
    public function bulkInsert(array $voices): int
    {
        $count = 0;
        foreach ($voices as $voice) {
            if ($this->upsert($voice)) {
                $count++;
            }
        }
        return $count;
    }
}

