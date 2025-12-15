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

        return [
            'success' => true,
            'message' => "Synced {$result['count']} voices from HeyGen",
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
            return ['success' => false, 'error' => $response['error'] ?? 'Failed to fetch voices'];
        }

        $voices = $response['data']['voices'] ?? [];
        $formattedVoices = [];

        foreach ($voices as $voice) {
            $formattedVoices[] = [
                'heygen_voice_id' => $voice['voice_id'] ?? '',
                'name' => $voice['name'] ?? 'Unknown',
                'language' => $voice['language'] ?? null,
                'gender' => $voice['gender'] ?? null,
                'preview_url' => $voice['preview_audio'] ?? null,
                'is_premium' => $voice['is_premium'] ?? 0,
            ];
        }

        $count = $this->voiceRepository->bulkInsert($formattedVoices);

        return ['success' => true, 'count' => $count];
    }
}

