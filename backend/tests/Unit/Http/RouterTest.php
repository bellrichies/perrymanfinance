<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PerrymanFinance\Core\Container;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\MethodNotAllowedException;
use PerrymanFinance\Http\Middleware\MiddlewareDispatcher;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testGroupsAndRouteParametersAreMatched(): void
    {
        $container = new Container();
        $router = new Router($container, new MiddlewareDispatcher($container));
        $router->group('/api/v1', [], static function (Router $router): void {
            $router->get(
                '/articles/{slug}',
                static fn (Request $request) => (new ApiResponseFactory())->success([
                    'slug' => $request->route('slug'),
                ]),
            );
        });
        $response = $router->dispatch(new Request('GET', '/api/v1/articles/market-update'));
        self::assertSame('market-update', $response->body()['data']['slug']);
    }
    public function testKnownPathWithWrongMethodThrowsMethodNotAllowed(): void
    {
        $container = new Container();
        $router = new Router($container, new MiddlewareDispatcher($container));
        $router->get('/health', static fn () => (new ApiResponseFactory())->success());
        $this->expectException(MethodNotAllowedException::class);
        $router->dispatch(new Request('POST', '/health'));
    }
}
