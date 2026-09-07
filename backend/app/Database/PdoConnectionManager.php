<?php

declare(strict_types=1);

namespace PerrymanFinance\Database;

use PDO;
use PerrymanFinance\Config\Config;

final class PdoConnectionManager implements ConnectionInterface
{
    private ?PDO $connection = null;
    /** @var callable(string, string, string, array<int, mixed>): PDO */ private $factory;

    /** @param null|callable(string, string, string, array<int, mixed>): PDO $factory */
    public function __construct(private readonly Config $config, ?callable $factory = null)
    {
        $this->factory = $factory ?? static fn (
            string $dsn,
            string $username,
            string $password,
            array $options,
        ): PDO => new PDO($dsn, $username, $password, $options);
    }

    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }
        $dsn = $this->config->string('database.dsn');
        if ($dsn === '') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config->string('database.host'),
                $this->config->string('database.port'),
                $this->config->string('database.name')
            );
        }
        $factory = $this->factory;
        $this->connection = $factory(
            $dsn,
            $this->config->string('database.username'),
            $this->config->string('database.password'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
        if ((string) $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $this->connection->exec("SET time_zone = '+00:00'");
        }
        return $this->connection;
    }
}
