<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use PerrymanFinance\Database\Migrations\MigrationRegistry;
use PerrymanFinance\Database\Migrations\MigrationRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\SqliteConnection;

final class MigrationRunnerTest extends TestCase
{
    public function testMigrateStatusAndRollbackAreTracked(): void
    {
        $connections = SqliteConnection::memory();
        $runner = new MigrationRunner(
            $connections,
            new MigrationRegistry(dirname(__DIR__, 2) . '/Fixtures/migrations'),
        );
        self::assertSame(['001_create_widgets', '002_create_gadgets'], $runner->migrate());
        self::assertSame([], $runner->migrate());
        self::assertTrue($runner->status()[0]->applied);
        self::assertSame(['002_create_gadgets'], $runner->rollback());
        self::assertFalse($runner->status()[1]->applied);
    }

    public function testFailedTransactionalMigrationIsNotPartiallyAppliedOrRecorded(): void
    {
        $connections = SqliteConnection::memory();
        $runner = new MigrationRunner(
            $connections,
            new MigrationRegistry(dirname(__DIR__, 2) . '/Fixtures/failing-migrations'),
        );
        try {
            $runner->migrate();
        } catch (RuntimeException) {
            $tables = $connections->connection()->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'partial_table'",
            );
            if ($tables === false) {
                throw new RuntimeException('Unable to inspect the SQLite schema.');
            }
            self::assertFalse($tables->fetchColumn());
            self::assertFalse($runner->status()[0]->applied);
            return;
        }
        self::fail('The failing migration did not throw.');
    }
}
