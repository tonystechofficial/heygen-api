<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AvatarRepository;
use App\Services\HeygenService;

/**
 * Avatar Controller - handles avatar-related HTTP requests
 */
class AvatarController
{
    private AvatarRepository $avatarRepository;
    private HeygenService $heygenService;

    public function __construct()
    {
        $this->avatarRepository = new AvatarRepository();
        $this->heygenService = new HeygenService();
    }

    /**
     * GET /api/avatars - List all cached avatars
     */
    public function index(): array
    {
        // If cache is invalid or empty, sync from HeyGen
        if (!$this->avatarRepository->isCacheValid()) {
            $this->syncFromHeygen();
        }

        $avatars = $this->avatarRepository->findAll();
        return ['success' => true, 'data' => $avatars, 'code' => 200];
    }

    /**
     * POST /api/avatars/sync - Force sync from HeyGen
     */
    public function sync(): array
    {
        $result = $this->syncFromHeygen();
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'], 'code' => 500];
        }

        return [
            'success' => true,
            'message' => "Synced {$result['count']} avatars from HeyGen",
            'code' => 200,
        ];
    }

    /**
     * Sync avatars from HeyGen API
     */
    private function syncFromHeygen(): array
    {
        $response = $this->heygenService->listAvatars();

        if (!$response['success']) {
            return ['success' => false, 'error' => $response['error'] ?? 'Failed to fetch avatars'];
        }

        $avatars = $response['data']['avatars'] ?? [];
        $formattedAvatars = [];

        foreach ($avatars as $avatar) {
            $formattedAvatars[] = [
                'heygen_avatar_id' => $avatar['avatar_id'] ?? '',
                'name' => $avatar['avatar_name'] ?? 'Unknown',
                'preview_url' => $avatar['preview_image_url'] ?? null,
                'gender' => $avatar['gender'] ?? null,
                'avatar_type' => $avatar['type'] ?? 'public',
            ];
        }

        $count = $this->avatarRepository->bulkInsert($formattedAvatars);

        return ['success' => true, 'count' => $count];
    }
}

