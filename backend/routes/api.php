<?php

declare(strict_types=1);

use PerrymanFinance\Http\Controllers\HealthController;
use PerrymanFinance\Http\Router;

return static function (Router $router): void {
    $router->group('/api/v1', [], static function (Router $router): void {
        $router->get('/health', [HealthController::class, 'show']);
    });
};
