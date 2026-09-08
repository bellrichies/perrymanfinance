<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Middleware\SecurityHeadersMiddleware;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testProductionHttpsResponseIncludesSecurityAndPublicCacheHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware(new Config(['app' => ['env' => 'production']]));
        $response = $middleware->process(
            new Request('GET', '/api/v1/pages/about', ['X-Forwarded-Proto' => 'https']),
            static fn (): Response => new Response(['success' => true]),
        );

        self::assertStringContainsString("default-src 'self'", $response->headers()['Content-Security-Policy']);
        self::assertSame('nosniff', $response->headers()['X-Content-Type-Options']);
        self::assertSame('max-age=31536000; includeSubDomains', $response->headers()['Strict-Transport-Security']);
        self::assertSame('public, max-age=60, stale-while-revalidate=300', $response->headers()['Cache-Control']);
    }

    public function testAdminResponsesAreNotGivenPublicCacheHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware(new Config(['app' => ['env' => 'production']]));
        $response = $middleware->process(
            new Request('GET', '/api/v1/admin/pages', ['X-Forwarded-Proto' => 'https']),
            static fn (): Response => new Response(['success' => true]),
        );

        self::assertArrayNotHasKey('Cache-Control', $response->headers());
    }
}
