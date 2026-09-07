<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Migrations;

use PDO;
use PerrymanFinance\Database\ConnectionInterface;
use Throwable;

final readonly class MigrationRunner
{
    public function __construct(
        private ConnectionInterface $connections,
        private MigrationRegistry $registry,
    ) {
    }

    /** @return list<string> */
    public function migrate(): array
    {
        $connection = $this->connections->connection();
        $repository = new MigrationRepository($connection);
        $repository->ensureTable();
        $applied = $repository->applied();
        $batch = $repository->nextBatch();
        $completed = [];
        foreach ($this->registry->all() as $migration) {
            if (isset($applied[$migration->name()])) {
                continue;
            }
            $this->execute($connection, function () use ($connection, $migration, $repository, $batch): void {
                $migration->up($connection);
                $repository->record($migration->name(), $batch);
            });
            $completed[] = $migration->name();
        }
        return $completed;
    }

    /** @return list<string> */
    public function rollback(int $steps = 1): array
    {
        if ($steps < 1) {
            throw new \InvalidArgumentException('Rollback steps must be at least one.');
        }
        $connection = $this->connections->connection();
        $repository = new MigrationRepository($connection);
        $repository->ensureTable();
        $applied = $repository->applied();
        $migrations = array_reverse($this->registry->all());
        $completed = [];
        foreach ($migrations as $migration) {
            if (!isset($applied[$migration->name()]) || count($completed) >= $steps) {
                continue;
            }
            $this->execute($connection, function () use ($connection, $migration, $repository): void {
                $migration->down($connection);
                $repository->remove($migration->name());
            });
            $completed[] = $migration->name();
        }
        return $completed;
    }

    /** @return list<MigrationStatus> */
    public function status(): array
    {
        $repository = new MigrationRepository($this->connections->connection());
        $repository->ensureTable();
        $applied = $repository->applied();
        return array_map(
            static fn (Migration $migration): MigrationStatus => new MigrationStatus(
                $migration->name(),
                isset($applied[$migration->name()]),
                $applied[$migration->name()] ?? null,
            ),
            $this->registry->all(),
        );
    }

    private function execute(PDO $connection, callable $operation): void
    {
        $transactional = (string) $connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql';
        if ($transactional) {
            $connection->beginTransaction();
        }
        try {
            $operation();
            if ($transactional) {
                $connection->commit();
            }
        } catch (Throwable $exception) {
            if ($transactional && $connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }
}
