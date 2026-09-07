<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PerrymanFinance\Http\ApiResponseFactory;
use PHPUnit\Framework\TestCase;

final class ApiResponseFactoryTest extends TestCase
{
    public function testSuccessAndErrorEnvelopesAreStable(): void
    {
        $factory = new ApiResponseFactory();
        $success = ['success' => true, 'data' => ['id' => 1], 'meta' => (object) [], 'message' => null];
        $error = [
            'success' => false,
            'error' => ['code' => 'INVALID', 'message' => 'Invalid.', 'fields' => (object) []],
        ];
        self::assertEquals($success, $factory->success(['id' => 1])->body());
        self::assertEquals($error, $factory->error('INVALID', 'Invalid.', 422)->body());
    }
}
