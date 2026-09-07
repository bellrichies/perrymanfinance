<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Core\Container;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final readonly class MiddlewareDispatcher
{
    public function __construct(private Container $container)
    {
    }

    /** @param list<class-string|object> $middleware @param callable(Request): Response $destination */
    public function dispatch(Request $request, array $middleware, callable $destination): Response
    {
        $next = $destination;
        foreach (array_reverse($middleware) as $definition) {
            $downstream = $next;
            $next = function (Request $request) use ($definition, $downstream): Response {
                $instance = is_string($definition) ? $this->container->get($definition) : $definition;
                if (!$instance instanceof MiddlewareInterface) {
                    throw new \LogicException('Middleware must implement MiddlewareInterface.');
                }
                return $instance->process($request, $downstream);
            };
        }
        return $next($request);
    }
}
