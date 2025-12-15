<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Video Repository - handles all database operations for videos
 * Follows Repository Pattern and Single Responsibility Principle
 */
class VideoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new video generation record
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO video_generations 
                (title, script, avatar_id, voice_id, status, width, height, background_color, background_image_url, speed)
                VALUES (:title, :script, :avatar_id, :voice_id, :status, :width, :height, :background_color, :background_image_url, :speed)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'script' => $data['script'],
            'avatar_id' => $data['avatar_id'],
            'voice_id' => $data['voice_id'],
            'status' => $data['status'] ?? 'pending',
            'width' => $data['width'] ?? 1920,
            'height' => $data['height'] ?? 1080,
            'background_color' => $data['background_color'] ?? '#FFFFFF',
            'background_image_url' => $data['background_image_url'] ?? null,
            'speed' => $data['speed'] ?? 1.0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find video by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM video_generations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find video by HeyGen video ID
     */
    public function findByHeygenId(string $heygenVideoId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM video_generations WHERE heygen_video_id = :heygen_video_id");
        $stmt->execute(['heygen_video_id' => $heygenVideoId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all videos with pagination
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM video_generations ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update video record
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "UPDATE video_generations SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update video by HeyGen ID (for webhook)
     */
    public function updateByHeygenId(string $heygenVideoId, array $data): bool
    {
        $fields = [];
        $params = ['heygen_video_id' => $heygenVideoId];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "UPDATE video_generations SET " . implode(', ', $fields) . " WHERE heygen_video_id = :heygen_video_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete video by ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM video_generations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Count total videos
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM video_generations");
        return (int) $stmt->fetchColumn();
    }
}

