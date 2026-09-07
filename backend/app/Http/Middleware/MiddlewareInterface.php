<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

interface MiddlewareInterface
{
    /** @param callable(Request): Response $next */
    public function process(Request $request, callable $next): Response;
}
