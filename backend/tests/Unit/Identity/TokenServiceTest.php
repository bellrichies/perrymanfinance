<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Services\Identity\TokenService;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    public function testExpiredAccessTokenIsRejected(): void
    {
        $service = new TokenService(new Config([
            'auth' => ['jwt_secret' => str_repeat('k', 48), 'access_ttl' => -1],
        ]));
        $token = $service->issueAccessToken(1, 'uuid', ['pages.view'])['access_token'];
        $this->expectException(UnauthorizedException::class);
        $service->verifyAccessToken($token);
    }

    public function testTamperedAccessTokenIsRejected(): void
    {
        $service = new TokenService(new Config([
            'auth' => ['jwt_secret' => str_repeat('k', 48), 'access_ttl' => 60],
        ]));
        $token = $service->issueAccessToken(1, 'uuid', ['pages.view'])['access_token'];
        $this->expectException(UnauthorizedException::class);
        $service->verifyAccessToken($token . 'tampered');
    }
}
