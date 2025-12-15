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
     * GET /api/avatar-groups - List all avatar groups from HeyGen
     */
    public function groups(): array
    {
        $response = $this->heygenService->listAvatarGroups();

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to fetch avatar groups',
                'code' => 500,
            ];
        }

        $groups = $response['data']['avatar_groups'] ?? $response['avatar_groups'] ?? [];

        return ['success' => true, 'data' => $groups, 'code' => 200];
    }

    /**
     * GET /api/avatar-groups/{group_id}/avatars - List avatars in a group
     */
    public function avatarsByGroup(string $groupId): array
    {
        $response = $this->heygenService->listAvatarsByGroup($groupId);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? $response['message'] ?? 'Failed to fetch avatars',
                'code' => 500,
            ];
        }

        $avatars = $response['data']['avatars'] ?? $response['avatars'] ?? [];

        return ['success' => true, 'data' => $avatars, 'code' => 200];
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

        $avatars = $this->avatarRepository->findAll();

        return [
            'success' => true,
            'message' => "Synced {$result['count']} avatars from HeyGen",
            'data' => $avatars,
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
            return ['success' => false, 'error' => $response['error'] ?? $response['message'] ?? 'Failed to fetch avatars'];
        }

        // HeyGen API V2 returns data.avatars array
        $avatars = $response['data']['avatars'] ?? $response['avatars'] ?? [];
        $formattedAvatars = [];

        foreach ($avatars as $avatar) {
            // Handle both possible key formats from HeyGen API
            $avatarId = $avatar['avatar_id'] ?? $avatar['id'] ?? '';
            $avatarName = $avatar['avatar_name'] ?? $avatar['name'] ?? 'Unknown';

            if (empty($avatarId)) continue;

            $formattedAvatars[] = [
                'heygen_avatar_id' => $avatarId,
                'name' => $avatarName,
                'preview_url' => $avatar['preview_image_url'] ?? $avatar['preview_url'] ?? $avatar['thumbnail_url'] ?? null,
                'gender' => $avatar['gender'] ?? null,
                'avatar_type' => $avatar['type'] ?? $avatar['avatar_type'] ?? 'public',
            ];
        }

        $count = $this->avatarRepository->bulkInsert($formattedAvatars);

        return ['success' => true, 'count' => $count];
    }
}

