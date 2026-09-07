<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use JsonException;
use PerrymanFinance\Http\Exceptions\BadRequestException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final class JsonBodyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if (str_starts_with($contentType, 'application/json') && $request->rawBody() !== '') {
            try {
                $decoded = json_decode($request->rawBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new BadRequestException('The request body contains invalid JSON.');
            }
            if (!is_array($decoded)) {
                throw new BadRequestException('The JSON request body must be an object.');
            }
            $request->setBody($decoded);
        }
        return $next($request);
    }
}
