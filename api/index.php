<?php

declare(strict_types=1);

/**
 * API Router - Main entry point
 * Handles all API requests and routes them to appropriate controllers
 */

// Error reporting - enable display for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // App\Controllers\AvatarController -> Controllers\AvatarController
    $relativeClass = substr($class, $len);

    // Split into parts: ['Controllers', 'AvatarController']
    $parts = explode('\\', $relativeClass);

    // Lowercase the directory names, keep filename as-is
    $fileName = array_pop($parts);
    $dirPath = implode('/', array_map('strtolower', $parts));

    $file = $baseDir . ($dirPath ? $dirPath . '/' : '') . $fileName . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$path = preg_replace('#^/api#', '', $path);
$path = rtrim($path, '/') ?: '/';

// Simple router
try {
    $response = routeRequest($method, $path);
} catch (\Throwable $e) {
    $response = [
        'success' => false,
        'error' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : 'Internal server error',
        'code' => 500,
    ];
}

// Send response
$code = $response['code'] ?? 200;
unset($response['code']);
http_response_code($code);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * Route the request to the appropriate controller
 */
function routeRequest(string $method, string $path): array
{
    // Video routes
    if ($method === 'POST' && $path === '/videos/generate') {
        $controller = new \App\Controllers\VideoController();
        return $controller->generate();
    }

    if ($method === 'GET' && preg_match('#^/videos/(\d+)/status$#', $path, $matches)) {
        $controller = new \App\Controllers\VideoController();
        return $controller->status((int) $matches[1]);
    }

    if ($method === 'GET' && preg_match('#^/videos/(\d+)$#', $path, $matches)) {
        $controller = new \App\Controllers\VideoController();
        return $controller->show((int) $matches[1]);
    }

    if ($method === 'GET' && $path === '/videos') {
        $controller = new \App\Controllers\VideoController();
        return $controller->index();
    }

    if ($method === 'DELETE' && preg_match('#^/videos/(\d+)$#', $path, $matches)) {
        $controller = new \App\Controllers\VideoController();
        return $controller->delete((int) $matches[1]);
    }

    // Avatar group routes
    if ($method === 'GET' && $path === '/avatar-groups') {
        $controller = new \App\Controllers\AvatarController();
        return $controller->groups();
    }

    if ($method === 'GET' && preg_match('#^/avatar-groups/([^/]+)/avatars$#', $path, $matches)) {
        $controller = new \App\Controllers\AvatarController();
        return $controller->avatarsByGroup($matches[1]);
    }

    // Avatar routes
    if ($method === 'GET' && $path === '/avatars') {
        $controller = new \App\Controllers\AvatarController();
        return $controller->index();
    }

    if ($method === 'POST' && $path === '/avatars/sync') {
        $controller = new \App\Controllers\AvatarController();
        return $controller->sync();
    }

    // Voice routes
    if ($method === 'GET' && $path === '/voices') {
        $controller = new \App\Controllers\VoiceController();
        return $controller->index();
    }

    if ($method === 'POST' && $path === '/voices/sync') {
        $controller = new \App\Controllers\VoiceController();
        return $controller->sync();
    }

    // Webhook routes
    if ($method === 'POST' && $path === '/webhook/heygen') {
        $controller = new \App\Controllers\WebhookController();
        return $controller->heygen();
    }

    // Health check
    if ($method === 'GET' && ($path === '/' || $path === '/health')) {
        return ['success' => true, 'message' => 'HeyGen API is running', 'code' => 200];
    }

    return ['success' => false, 'error' => 'Route not found', 'code' => 404];
}

