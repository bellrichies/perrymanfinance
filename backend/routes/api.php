<?php

declare(strict_types=1);

use PerrymanFinance\Http\Controllers\HealthController;
use PerrymanFinance\Http\Controllers\AdminAuthController;
use PerrymanFinance\Http\Middleware\AuthMiddleware;
use PerrymanFinance\Http\Router;

return static function (Router $router): void {
    $router->group('/api/v1', [], static function (Router $router): void {
        $router->get('/health', [HealthController::class, 'show']);
        $router->group('/admin/auth', [], static function (Router $router): void {
            $router->post('/login', [AdminAuthController::class, 'login']);
            $router->post('/refresh', [AdminAuthController::class, 'refresh']);
            $router->post('/logout', [AdminAuthController::class, 'logout']);
            $router->post('/forgot-password', [AdminAuthController::class, 'forgotPassword']);
            $router->post('/reset-password', [AdminAuthController::class, 'resetPassword']);
            $router->get('/me', [AdminAuthController::class, 'me'], [AuthMiddleware::class]);
        });
    });
};
