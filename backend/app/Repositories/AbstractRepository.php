<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

use PDO;
use PDOStatement;
use PerrymanFinance\Database\ConnectionInterface;
use InvalidArgumentException;

abstract class AbstractRepository
{
    public function __construct(private readonly ConnectionInterface $connections)
    {
    }
    protected function connection(): PDO
    {
        return $this->connections->connection();
    }

    /** @param array<string|int, mixed> $parameters */
    protected function execute(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($parameters);
        return $statement;
    }

    /** @param list<string> $allowed */
    protected function allowedIdentifier(string $identifier, array $allowed): string
    {
        if (!in_array($identifier, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported query field.');
        }
        return $identifier;
    }
}
