<?php

declare(strict_types=1);

use PerrymanFinance\Config\Config;
use PerrymanFinance\Config\Environment;
use PerrymanFinance\Core\Application;
use PerrymanFinance\Core\Container;
use PerrymanFinance\Database\ConnectionInterface;
use PerrymanFinance\Database\PdoConnectionManager;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\ExceptionHandler;
use PerrymanFinance\Http\Middleware\CorsMiddleware;
use PerrymanFinance\Http\Middleware\JsonBodyMiddleware;
use PerrymanFinance\Http\Middleware\MiddlewareDispatcher;
use PerrymanFinance\Http\Middleware\RequestIdMiddleware;
use PerrymanFinance\Http\Middleware\SecurityHeadersMiddleware;
use PerrymanFinance\Http\Router;
use PerrymanFinance\Logging\JsonLogger;
use PerrymanFinance\Logging\LoggerInterface;
use PerrymanFinance\Services\Identity\NativeMailPasswordResetNotifier;
use PerrymanFinance\Services\Identity\PasswordResetNotifierInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);
$config = Environment::load($basePath);
$container = new Container();
$container->instance(Config::class, $config);
$container->instance(Container::class, $container);
$container->singleton(ApiResponseFactory::class);
$container->singleton(LoggerInterface::class, static fn (): LoggerInterface => new JsonLogger($config->string('logging.path')));
$container->singleton(ConnectionInterface::class, PdoConnectionManager::class);
$container->singleton(PasswordResetNotifierInterface::class, NativeMailPasswordResetNotifier::class);
$container->singleton(MiddlewareDispatcher::class);

/** @var MiddlewareDispatcher $dispatcher */
$dispatcher = $container->get(MiddlewareDispatcher::class);
$router = new Router($container, $dispatcher);
$routes = require $basePath . '/routes/api.php';
$routes($router);

return new Application(
    $router,
    $dispatcher,
    $container->get(ExceptionHandler::class),
    [RequestIdMiddleware::class, SecurityHeadersMiddleware::class, CorsMiddleware::class, JsonBodyMiddleware::class],
);
