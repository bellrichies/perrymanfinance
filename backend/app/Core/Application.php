<?php

declare(strict_types=1);

namespace PerrymanFinance\Core;

use PerrymanFinance\Http\Exceptions\ExceptionHandler;
use PerrymanFinance\Http\Middleware\MiddlewareDispatcher;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Http\Router;
use Throwable;

final readonly class Application
{
    /** @param list<class-string|object> $middleware */
    public function __construct(
        private Router $router,
        private MiddlewareDispatcher $dispatcher,
        private ExceptionHandler $exceptions,
        private array $middleware = [],
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->dispatcher->dispatch(
                $request,
                $this->middleware,
                fn (Request $request): Response => $this->router->dispatch($request),
            );
        } catch (Throwable $exception) {
            return $this->exceptions->render($exception, $request);
        }
    }
}
