<?php

declare(strict_types=1);

namespace Tests\Feature;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Controllers\HealthController;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Router;
use PHPUnit\Framework\TestCase;
use Tests\Support\ApplicationFactory;

final class HealthEndpointTest extends TestCase
{
    public function testHealthEndpointUsesTheStandardSuccessEnvelope(): void
    {
        $config = new Config(['app' => ['version' => 'test', 'debug' => false]]);
        $app = ApplicationFactory::create(static function (Router $router) use ($config): void {
            $controller = new HealthController($config, new ApiResponseFactory());
            $router->get('/api/v1/health', [$controller, 'show']);
        }, $config);
        $response = $app->handle(new Request('GET', '/api/v1/health'));
        self::assertSame(200, $response->status());
        self::assertSame('test', $response->body()['data']['version']);
    }
}
