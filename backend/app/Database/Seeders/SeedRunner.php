<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Seeders;

use PerrymanFinance\Database\ConnectionInterface;
use RuntimeException;
use Throwable;

final readonly class SeedRunner
{
    /** @param list<Seeder> $seeders */
    public function __construct(
        private ConnectionInterface $connections,
        private array $seeders,
        private string $environment,
    ) {
    }

    /** @return list<string> */
    public function run(): array
    {
        if (!in_array($this->environment, ['local', 'development', 'testing'], true)) {
            throw new RuntimeException('Seeders may run only in local, development, or testing environments.');
        }
        $completed = [];
        foreach ($this->seeders as $seeder) {
            $connection = $this->connections->connection();
            $connection->beginTransaction();
            try {
                $seeder->run($connection);
                $connection->commit();
            } catch (Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $exception;
            }
            $completed[] = $seeder->name();
        }
        return $completed;
    }
}
