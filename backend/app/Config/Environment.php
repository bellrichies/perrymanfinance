<?php

declare(strict_types=1);

namespace PerrymanFinance\Config;

use Dotenv\Dotenv;

final class Environment
{
    public static function load(string $basePath): Config
    {
        if (is_file($basePath . '/.env')) {
            Dotenv::createImmutable($basePath)->safeLoad();
        }
        $env = static fn (string $key, string $default = ''): string =>
            is_string($_ENV[$key] ?? null) ? $_ENV[$key] : (getenv($key) ?: $default);

        return new Config([
            'app' => [
                'env' => $env('APP_ENV', 'production'),
                'debug' => $env('APP_DEBUG', 'false'),
                'version' => $env('APP_VERSION', 'dev'),
                'url' => rtrim($env('APP_URL', 'http://localhost:8090'), '/'),
                'frontend_url' => rtrim($env('FRONTEND_URL', 'http://localhost:5173'), '/'),
            ],
            'auth' => [
                'jwt_secret' => $env('JWT_SECRET'),
                'access_ttl' => (int) $env('ACCESS_TOKEN_TTL', '900'),
                'refresh_ttl' => (int) $env('REFRESH_TOKEN_TTL', '1209600'),
                'reset_ttl' => (int) $env('PASSWORD_RESET_TTL', '3600'),
                'cookie_secure' => $env('AUTH_COOKIE_SECURE', 'true'),
                'login_limit' => (int) $env('AUTH_LOGIN_LIMIT', '5'),
                'reset_limit' => (int) $env('AUTH_RESET_LIMIT', '3'),
                'rate_window' => (int) $env('AUTH_RATE_WINDOW', '900'),
            ],
            'database' => [
                'dsn' => $env('DB_DSN'), 'host' => $env('DB_HOST', '127.0.0.1'),
                'port' => $env('DB_PORT', '3306'), 'name' => $env('DB_DATABASE', 'perrymanfinance'),
                'username' => $env('DB_USERNAME'), 'password' => $env('DB_PASSWORD'),
            ],
            'http' => [
                'cors_origins' => array_values(array_filter(array_map(
                    'trim',
                    explode(',', $env('CORS_ALLOWED_ORIGINS')),
                ))),
            ],
            'logging' => ['path' => $env('LOG_PATH', $basePath . '/storage/logs/app.log')],
            'mail' => ['from' => $env('MAIL_FROM_ADDRESS')],
            'media' => [
                'path' => $env('MEDIA_STORAGE_PATH') ?: $basePath . '/storage/uploads',
                'max_bytes' => (int) $env('MEDIA_MAX_BYTES', '5242880'),
                'max_dimension' => (int) $env('MEDIA_MAX_DIMENSION', '6000'),
            ],
        ]);
    }
}
