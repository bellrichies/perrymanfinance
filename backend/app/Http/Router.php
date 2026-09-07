<?php

declare(strict_types=1);

namespace PerrymanFinance\Http;

use PerrymanFinance\Core\Container;
use PerrymanFinance\Http\Exceptions\MethodNotAllowedException;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Middleware\MiddlewareDispatcher;

final class Router
{
    /** @var list<Route> */ private array $routes = [];
    private string $prefix = '';
    /** @var list<class-string|object> */ private array $groupMiddleware = [];

    public function __construct(
        private readonly Container $container,
        private readonly MiddlewareDispatcher $dispatcher,
    ) {
    }

    /**
     * @param callable|array{class-string, string} $handler
     * @param list<class-string|object> $middleware
     */
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = new Route(
            strtoupper($method),
            $this->prefix . $path,
            $handler,
            [...$this->groupMiddleware, ...$middleware],
        );
    }
    /**
     * @param callable|array{class-string, string} $handler
     * @param list<class-string|object> $middleware
     */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }
    /**
     * @param callable|array{class-string, string} $handler
     * @param list<class-string|object> $middleware
     */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param list<class-string|object> $middleware */
    public function group(string $prefix, array $middleware, callable $routes): void
    {
        $oldPrefix = $this->prefix;
        $oldMiddleware = $this->groupMiddleware;
        $this->prefix .= rtrim($prefix, '/');
        $this->groupMiddleware = [...$this->groupMiddleware, ...$middleware];
        try {
            $routes($this);
        } finally {
            $this->prefix = $oldPrefix;
            $this->groupMiddleware = $oldMiddleware;
        }
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            $params = $route->match($request->uri());
            if ($params === null) {
                continue;
            }
            if ($route->method !== $request->method()) {
                $allowed[] = $route->method;
                continue;
            }
            $request->setRouteParams($params);
            return $this->dispatcher->dispatch(
                $request,
                $route->middleware,
                fn (Request $request): Response => $this->invoke($route, $request),
            );
        }
        if ($allowed !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowed)));
        }
        throw new NotFoundException();
    }

    private function invoke(Route $route, Request $request): Response
    {
        $handler = $route->handler;
        if (is_array($handler) && is_string($handler[0])) {
            $handler = [$this->container->get($handler[0]), $handler[1]];
        }
        if (!is_callable($handler)) {
            throw new \LogicException('Route handler is not callable.');
        }
        $response = $handler($request);
        if (!$response instanceof Response) {
            throw new \LogicException('Route handlers must return a Response.');
        }
        return $response;
    }
}
