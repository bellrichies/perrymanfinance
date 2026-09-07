<?php

declare(strict_types=1);

namespace Tests\Support;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Core\Application;
use PerrymanFinance\Core\Container;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\ExceptionHandler;
use PerrymanFinance\Http\Middleware\MiddlewareDispatcher;
use PerrymanFinance\Http\Router;

final class ApplicationFactory
{
    /** @param callable(Router): void $routes */
    public static function create(callable $routes, ?Config $config = null): Application
    {
        $config ??= new Config(['app' => ['debug' => false]]);
        $container = new Container();
        $container->instance(Config::class, $config);
        $container->instance(Container::class, $container);
        $dispatcher = new MiddlewareDispatcher($container);
        $router = new Router($container, $dispatcher);
        $routes($router);
        $handler = new ExceptionHandler(new ApiResponseFactory(), new InMemoryLogger(), $config);
        return new Application($router, $dispatcher, $handler);
    }
}
