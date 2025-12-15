<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VideoRepository;
use App\Services\HeygenService;

/**
 * Video Controller - handles video-related HTTP requests
 * Follows Single Responsibility - only handles video endpoints
 */
class VideoController
{
    private VideoRepository $videoRepository;
    private HeygenService $heygenService;

    public function __construct()
    {
        $this->videoRepository = new VideoRepository();
        $this->heygenService = new HeygenService();
    }

    /**
     * POST /api/videos/generate - Generate a new video
     */
    public function generate(): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validation
        $required = ['script', 'avatar_id', 'voice_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}", 'code' => 400];
            }
        }

        // Prepare data
        $data = [
            'title' => $input['title'] ?? 'Untitled Video',
            'script' => $input['script'],
            'avatar_id' => $input['avatar_id'],
            'voice_id' => $input['voice_id'],
            'width' => $input['settings']['width'] ?? 1920,
            'height' => $input['settings']['height'] ?? 1080,
            'background_color' => $input['settings']['background_color'] ?? '#FFFFFF',
            'background_image_url' => $input['settings']['background_image_url'] ?? null,
            'speed' => $input['settings']['speed'] ?? 1.0,
            'status' => 'pending',
        ];

        // Save to database
        $videoId = $this->videoRepository->create($data);

        // Call HeyGen API
        $response = $this->heygenService->generateVideo($data);

        if (!$response['success']) {
            $this->videoRepository->update($videoId, [
                'status' => 'failed',
                'error_message' => $response['error'] ?? 'Unknown error',
            ]);
            return ['success' => false, 'error' => $response['error'] ?? 'HeyGen API error', 'code' => 500];
        }

        // Update with HeyGen video ID
        $heygenVideoId = $response['data']['video_id'] ?? null;
        $this->videoRepository->update($videoId, [
            'heygen_video_id' => $heygenVideoId,
            'status' => 'processing',
        ]);

        return [
            'success' => true,
            'data' => [
                'id' => $videoId,
                'heygen_video_id' => $heygenVideoId,
                'status' => 'processing',
            ],
            'code' => 201,
        ];
    }

    /**
     * GET /api/videos/{id}/status - Get video status
     */
    public function status(int $id): array
    {
        $video = $this->videoRepository->findById($id);
        if (!$video) {
            return ['success' => false, 'error' => 'Video not found', 'code' => 404];
        }

        // If still processing, check HeyGen API
        if ($video['status'] === 'processing' && !empty($video['heygen_video_id'])) {
            $response = $this->heygenService->getVideoStatus($video['heygen_video_id']);

            if ($response['success'] && isset($response['data'])) {
                $heygenStatus = $response['data']['status'] ?? 'processing';
                $updateData = ['status' => $this->mapHeygenStatus($heygenStatus)];

                if ($heygenStatus === 'completed') {
                    $updateData['video_url'] = $response['data']['video_url'] ?? null;
                    $updateData['thumbnail_url'] = $response['data']['thumbnail_url'] ?? null;
                    $updateData['duration'] = $response['data']['duration'] ?? null;
                } elseif ($heygenStatus === 'failed') {
                    $error = $response['data']['error'] ?? 'Generation failed';
                    $updateData['error_message'] = is_array($error) ? json_encode($error) : $error;
                }

                $this->videoRepository->update($id, $updateData);
                $video = array_merge($video, $updateData);
            }
        }

        return ['success' => true, 'data' => $video, 'code' => 200];
    }

    /**
     * GET /api/videos - List all videos
     * Auto-checks status for videos in 'processing' state
     */
    public function index(): array
    {
        $limit = (int) ($_GET['limit'] ?? 50);
        $offset = (int) ($_GET['offset'] ?? 0);
        $videos = $this->videoRepository->findAll($limit, $offset);
        $total = $this->videoRepository->count();

        // Auto-check status for processing videos
        foreach ($videos as &$video) {
            if ($video['status'] === 'processing' && !empty($video['heygen_video_id'])) {
                $video = $this->refreshVideoStatus($video);
            }
        }

        return ['success' => true, 'data' => $videos, 'total' => $total, 'code' => 200];
    }

    /**
     * Refresh video status from HeyGen API and update DB
     */
    private function refreshVideoStatus(array $video): array
    {
        $response = $this->heygenService->getVideoStatus($video['heygen_video_id']);

        if (!$response['success'] || !isset($response['data'])) {
            return $video;
        }

        $heygenStatus = $response['data']['status'] ?? 'processing';
        $newStatus = $this->mapHeygenStatus($heygenStatus);

        // Only update if status changed
        if ($newStatus !== $video['status']) {
            $updateData = ['status' => $newStatus];

            if ($heygenStatus === 'completed') {
                $updateData['video_url'] = $response['data']['video_url'] ?? null;
                $updateData['thumbnail_url'] = $response['data']['thumbnail_url'] ?? null;
                $updateData['duration'] = $response['data']['duration'] ?? null;
            } elseif ($heygenStatus === 'failed') {
                $error = $response['data']['error'] ?? 'Generation failed';
                $updateData['error_message'] = is_array($error) ? json_encode($error) : $error;
            }

            $this->videoRepository->update($video['id'], $updateData);
            $video = array_merge($video, $updateData);
        }

        return $video;
    }

    /**
     * GET /api/videos/{id} - Get single video
     */
    public function show(int $id): array
    {
        $video = $this->videoRepository->findById($id);
        if (!$video) {
            return ['success' => false, 'error' => 'Video not found', 'code' => 404];
        }
        return ['success' => true, 'data' => $video, 'code' => 200];
    }

    /**
     * DELETE /api/videos/{id} - Delete video
     */
    public function delete(int $id): array
    {
        $video = $this->videoRepository->findById($id);
        if (!$video) {
            return ['success' => false, 'error' => 'Video not found', 'code' => 404];
        }
        $this->videoRepository->delete($id);
        return ['success' => true, 'message' => 'Video deleted', 'code' => 200];
    }

    private function mapHeygenStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'failed' => 'failed',
            'pending', 'processing' => 'processing',
            default => 'processing',
        };
    }
}

