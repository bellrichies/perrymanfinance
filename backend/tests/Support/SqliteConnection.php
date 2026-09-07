<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use PerrymanFinance\Database\ConnectionInterface;

final readonly class SqliteConnection implements ConnectionInterface
{
    public function __construct(private PDO $pdo)
    {
    }
    public static function memory(): self
    {
        return new self(new PDO('sqlite::memory:'));
    }
    public function connection(): PDO
    {
        return $this->pdo;
    }
}
