<?php

declare(strict_types=1);

namespace App\Services;

/**
 * HeyGen API Client Service
 * Follows Single Responsibility Principle - handles all HeyGen API interactions
 * Follows Interface Segregation - methods are focused and specific
 */
class HeygenService
{
    private string $apiKey;
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = $_ENV['HEYGEN_API_KEY'] ?? '';
        $this->apiUrl = $_ENV['HEYGEN_API_URL'] ?? 'https://api.heygen.com';
        $this->timeout = 30;
    }

    /**
     * Generate a video with talking avatar
     */
    public function generateVideo(array $params): array
    {
        $payload = [
            'video_inputs' => [
                [
                    'character' => [
                        'type' => 'avatar',
                        'avatar_id' => $params['avatar_id'],
                        'avatar_style' => 'normal',
                    ],
                    'voice' => [
                        'type' => 'text',
                        'input_text' => $params['script'],
                        'voice_id' => $params['voice_id'],
                        'speed' => $params['speed'] ?? 1.0,
                    ],
                    'background' => $this->buildBackground($params),
                ],
            ],
            'dimension' => [
                'width' => $params['width'] ?? 1920,
                'height' => $params['height'] ?? 1080,
            ],
        ];

        if (!empty($params['title'])) {
            $payload['title'] = $params['title'];
        }

        return $this->request('POST', '/v2/video/generate', $payload);
    }

    /**
     * Get video status from HeyGen
     */
    public function getVideoStatus(string $videoId): array
    {
        return $this->request('GET', "/v1/video_status.get?video_id={$videoId}");
    }

    /**
     * List available avatars
     */
    public function listAvatars(): array
    {
        return $this->request('GET', '/v2/avatars');
    }

    /**
     * List available voices
     */
    public function listVoices(): array
    {
        return $this->request('GET', '/v2/voices');
    }

    /**
     * Build background configuration
     */
    private function buildBackground(array $params): array
    {
        if (!empty($params['background_image_url'])) {
            return [
                'type' => 'image',
                'url' => $params['background_image_url'],
            ];
        }

        return [
            'type' => 'color',
            'value' => $params['background_color'] ?? '#FFFFFF',
        ];
    }

    /**
     * Make HTTP request to HeyGen API
     */
    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $decoded = json_decode($response, true) ?? [];
        $decoded['http_code'] = $httpCode;
        $decoded['success'] = $httpCode >= 200 && $httpCode < 300;

        return $decoded;
    }
}

