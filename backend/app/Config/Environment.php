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
        ]);
    }
}
