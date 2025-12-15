<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VoiceRepository;
use App\Services\HeygenService;

/**
 * Voice Controller - handles voice-related HTTP requests
 */
class VoiceController
{
    private VoiceRepository $voiceRepository;
    private HeygenService $heygenService;

    public function __construct()
    {
        $this->voiceRepository = new VoiceRepository();
        $this->heygenService = new HeygenService();
    }

    /**
     * GET /api/voices - List all cached voices
     */
    public function index(): array
    {
        // If cache is invalid or empty, sync from HeyGen
        if (!$this->voiceRepository->isCacheValid()) {
            $this->syncFromHeygen();
        }

        $language = $_GET['language'] ?? null;
        
        if ($language) {
            $voices = $this->voiceRepository->findByLanguage($language);
        } else {
            $voices = $this->voiceRepository->findAll();
        }

        return ['success' => true, 'data' => $voices, 'code' => 200];
    }

    /**
     * POST /api/voices/sync - Force sync from HeyGen
     */
    public function sync(): array
    {
        $result = $this->syncFromHeygen();

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'], 'code' => 500];
        }

        $voices = $this->voiceRepository->findAll();

        return [
            'success' => true,
            'message' => "Synced {$result['count']} voices from HeyGen",
            'data' => $voices,
            'code' => 200,
        ];
    }

    /**
     * Sync voices from HeyGen API
     */
    private function syncFromHeygen(): array
    {
        $response = $this->heygenService->listVoices();

        if (!$response['success']) {
            return ['success' => false, 'error' => $response['error'] ?? $response['message'] ?? 'Failed to fetch voices'];
        }

        // HeyGen API V2 returns data.voices array
        $voices = $response['data']['voices'] ?? $response['voices'] ?? [];
        $formattedVoices = [];

        foreach ($voices as $voice) {
            // Handle both possible key formats from HeyGen API
            $voiceId = $voice['voice_id'] ?? $voice['id'] ?? '';

            if (empty($voiceId)) continue;

            $formattedVoices[] = [
                'heygen_voice_id' => $voiceId,
                'name' => $voice['name'] ?? $voice['display_name'] ?? 'Unknown',
                'language' => $voice['language'] ?? $voice['locale'] ?? null,
                'gender' => $voice['gender'] ?? null,
                'preview_url' => $voice['preview_audio'] ?? $voice['sample_url'] ?? null,
                'is_premium' => $voice['is_premium'] ?? $voice['premium'] ?? 0,
            ];
        }

        $count = $this->voiceRepository->bulkInsert($formattedVoices);

        return ['success' => true, 'count' => $count];
    }
}

