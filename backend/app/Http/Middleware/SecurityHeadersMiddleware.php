<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final readonly class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: https:",
            "object-src 'none'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
        ]);
        $response = $next($request)
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->method() === 'GET' && !str_starts_with($request->uri(), '/api/v1/admin')) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
        }
        if ($this->config->string('app.env') === 'production' && $request->header('X-Forwarded-Proto') === 'https') {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        return $response;
    }
}
