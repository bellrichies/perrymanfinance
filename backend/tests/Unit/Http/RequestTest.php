<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PerrymanFinance\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testRequestExposesInputsAndRouteParameters(): void
    {
        $request = new Request(
            'POST',
            '/items/42',
            ['content-type' => 'application/json'],
            ['page' => 2],
            ['name' => 'Test'],
        );
        $request->setRouteParams(['id' => '42']);
        self::assertSame('application/json', $request->header('Content-Type'));
        self::assertSame(2, $request->input('page'));
        self::assertSame('Test', $request->input('name'));
        self::assertSame('42', $request->route('id'));
    }
}
