<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VideoRepository;

/**
 * Webhook Controller - handles HeyGen webhook callbacks
 */
class WebhookController
{
    private VideoRepository $videoRepository;

    public function __construct()
    {
        $this->videoRepository = new VideoRepository();
    }

    /**
     * POST /api/webhook/heygen - Handle HeyGen webhook
     */
    public function heygen(): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // Log webhook for debugging
        $this->logWebhook($input);

        // Validate webhook secret if configured
        $secret = $_ENV['WEBHOOK_SECRET'] ?? '';
        if (!empty($secret)) {
            $signature = $_SERVER['HTTP_X_HEYGEN_SIGNATURE'] ?? '';
            if (!$this->verifySignature($input, $signature, $secret)) {
                return ['success' => false, 'error' => 'Invalid signature', 'code' => 401];
            }
        }

        // Extract event data
        $eventType = $input['event_type'] ?? '';
        $eventData = $input['event_data'] ?? $input['data'] ?? [];

        if (empty($eventType)) {
            return ['success' => false, 'error' => 'Missing event_type', 'code' => 400];
        }

        // Handle different event types
        return match ($eventType) {
            'avatar_video.success', 'video.completed' => $this->handleVideoCompleted($eventData),
            'avatar_video.fail', 'video.failed' => $this->handleVideoFailed($eventData),
            default => ['success' => true, 'message' => 'Event ignored', 'code' => 200],
        };
    }

    /**
     * Handle video completed event
     */
    private function handleVideoCompleted(array $data): array
    {
        $videoId = $data['video_id'] ?? '';
        
        if (empty($videoId)) {
            return ['success' => false, 'error' => 'Missing video_id', 'code' => 400];
        }

        $updateData = [
            'status' => 'completed',
            'video_url' => $data['video_url'] ?? null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'duration' => $data['duration'] ?? null,
        ];

        $this->videoRepository->updateByHeygenId($videoId, $updateData);

        return ['success' => true, 'message' => 'Video marked as completed', 'code' => 200];
    }

    /**
     * Handle video failed event
     */
    private function handleVideoFailed(array $data): array
    {
        $videoId = $data['video_id'] ?? '';
        
        if (empty($videoId)) {
            return ['success' => false, 'error' => 'Missing video_id', 'code' => 400];
        }

        $updateData = [
            'status' => 'failed',
            'error_message' => $data['error'] ?? $data['message'] ?? 'Generation failed',
        ];

        $this->videoRepository->updateByHeygenId($videoId, $updateData);

        return ['success' => true, 'message' => 'Video marked as failed', 'code' => 200];
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(array $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Log webhook for debugging
     */
    private function logWebhook(array $data): void
    {
        $logFile = __DIR__ . '/../../logs/webhooks.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

