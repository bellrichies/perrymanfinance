<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $candidate = $request->header('X-Request-ID');
        $requestId = is_string($candidate) && preg_match('/^[A-Za-z0-9._-]{8,128}$/', $candidate) === 1
            ? $candidate : bin2hex(random_bytes(16));
        $request->setAttribute('request_id', $requestId);
        return $next($request)->withHeader('X-Request-ID', $requestId);
    }
}
