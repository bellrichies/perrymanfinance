<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PerrymanFinance\Core\Container;
use PHPUnit\Framework\TestCase;
use Tests\Support\ExampleContract;
use Tests\Support\ExampleService;

final class ContainerTest extends TestCase
{
    public function testInterfaceBindingAndSingletonLifecycle(): void
    {
        $container = new Container();
        $container->singleton(ExampleContract::class, ExampleService::class);
        self::assertInstanceOf(ExampleService::class, $container->get(ExampleContract::class));
        self::assertSame($container->get(ExampleContract::class), $container->get(ExampleContract::class));
    }
}
