<?php

declare(strict_types=1);

$candidateBackends = [
    dirname(__DIR__, 3) . '/private/backend/public/index.php',
    dirname(__DIR__, 2) . '/private/backend/public/index.php',
    dirname(__DIR__) . '/backend/public/index.php',
    dirname(__DIR__) . '/backend/index.php',
];

foreach ($candidateBackends as $backendFrontController) {
    if (is_file($backendFrontController)) {
        require $backendFrontController;
        return;
    }
}

http_response_code(500);
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'error' => [
        'code' => 'BACKEND_FRONT_CONTROLLER_NOT_FOUND',
        'message' => 'The API backend is not available in the deployed layout.',
        'fields' => [],
    ],
], JSON_THROW_ON_ERROR);
