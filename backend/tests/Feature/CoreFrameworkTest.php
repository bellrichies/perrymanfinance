<?php

declare(strict_types=1);

namespace Tests\Feature;

use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\ApplicationFactory;

final class CoreFrameworkTest extends TestCase
{
    public function testNotFoundAndMethodNotAllowedAreMappedToSafeApiErrors(): void
    {
        $app = ApplicationFactory::create(static fn (Router $router) => $router->get(
            '/known',
            static fn () => (new ApiResponseFactory())->success(),
        ));
        self::assertSame(404, $app->handle(new Request('GET', '/missing'))->status());
        $response = $app->handle(new Request('POST', '/known'));
        self::assertSame(405, $response->status());
        self::assertSame('GET', $response->headers()['Allow']);
    }
    public function testUnexpectedExceptionIsMasked(): void
    {
        $app = ApplicationFactory::create(static fn (Router $router) => $router->get(
            '/fail',
            static function (): never {
                throw new RuntimeException('database password leaked');
            },
        ));
        $response = $app->handle(new Request('GET', '/fail'));
        self::assertSame(500, $response->status());
        self::assertSame('An unexpected error occurred.', $response->body()['error']['message']);
    }
}
