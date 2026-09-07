<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final readonly class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $origin = $request->header('Origin');
        $allowed = $this->config->get('http.cors_origins', []);
        $response = $request->method() === 'OPTIONS' ? new Response([], 204) : $next($request);
        $response = $response
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Request-ID');
        return is_string($origin) && is_array($allowed) && in_array($origin, $allowed, true)
            ? $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true')
            : $response;
    }
}
