<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Identity;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;

final readonly class TokenService
{
    private string $secret;
    private int $accessTtl;

    public function __construct(Config $config)
    {
        $this->secret = $config->string('auth.jwt_secret');
        $this->accessTtl = (int) $config->get('auth.access_ttl', 900);
        if (strlen($this->secret) < 32) {
            throw new \RuntimeException('JWT_SECRET must contain at least 32 characters.');
        }
    }

    /**
     * @param list<string> $permissions
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function issueAccessToken(int $userId, string $uuid, array $permissions): array
    {
        $now = time();
        $payload = ['sub' => (string) $userId, 'uid' => $uuid, 'permissions' => $permissions, 'iat' => $now, 'exp' => $now + $this->accessTtl];
        return ['access_token' => $this->encode($payload), 'token_type' => 'Bearer', 'expires_in' => $this->accessTtl];
    }

    /** @return array<string, mixed> */
    public function verifyAccessToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new UnauthorizedException('The access token is invalid or expired.');
        }
        [$header, $payload, $signature] = $parts;
        $expected = $this->base64Url(hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true));
        if (!hash_equals($expected, $signature)) {
            throw new UnauthorizedException('The access token is invalid or expired.');
        }
        $decoded = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($decoded) || !is_numeric($decoded['sub'] ?? null) || (int) ($decoded['exp'] ?? 0) <= time()) {
            throw new UnauthorizedException('The access token is invalid or expired.');
        }
        return $decoded;
    }

    public function randomToken(): string
    {
        return $this->base64Url(random_bytes(48));
    }

    public function hashOpaqueToken(string $token): string
    {
        return hash_hmac('sha256', $token, $this->secret);
    }

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = $this->base64Url(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64Url(hash_hmac('sha256', "{$header}.{$body}", $this->secret, true));
        return "{$header}.{$body}.{$signature}";
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return is_string($decoded) ? $decoded : '';
    }
}
