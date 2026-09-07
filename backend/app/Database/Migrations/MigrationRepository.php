<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Migrations;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final readonly class MigrationRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function ensureTable(): void
    {
        $driver = (string) $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'mysql'
            ? 'CREATE TABLE IF NOT EXISTS schema_migrations ('
                . 'migration VARCHAR(191) NOT NULL PRIMARY KEY, batch INT UNSIGNED NOT NULL, '
                . 'migrated_at DATETIME(6) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            : 'CREATE TABLE IF NOT EXISTS schema_migrations ('
                . 'migration VARCHAR(191) NOT NULL PRIMARY KEY, batch INTEGER NOT NULL, migrated_at TEXT NOT NULL)';
        $this->connection->exec($sql);
    }

    /** @return array<string, int> */
    public function applied(): array
    {
        $statement = $this->connection->query('SELECT migration, batch FROM schema_migrations ORDER BY migration');
        $rows = $statement === false ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
        $applied = [];
        foreach ($rows as $row) {
            $applied[(string) $row['migration']] = (int) $row['batch'];
        }
        return $applied;
    }

    public function nextBatch(): int
    {
        $value = $this->connection->query('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations');
        return ($value === false ? 0 : (int) $value->fetchColumn()) + 1;
    }

    public function record(string $name, int $batch): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO schema_migrations (migration, batch, migrated_at) VALUES (:migration, :batch, :migrated_at)',
        );
        $statement->execute([
            'migration' => $name,
            'batch' => $batch,
            'migrated_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u'),
        ]);
    }

    public function remove(string $name): void
    {
        $statement = $this->connection->prepare('DELETE FROM schema_migrations WHERE migration = :migration');
        $statement->execute(['migration' => $name]);
    }
}
