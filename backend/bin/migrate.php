<?php

declare(strict_types=1);

use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\ConnectionInterface;
use PerrymanFinance\Database\Migrations\MigrationRegistry;
use PerrymanFinance\Database\Migrations\MigrationRunner;

/** @var array{base_path: string, config: Config, connections: ConnectionInterface} $bootstrap */
$bootstrap = require dirname(__DIR__) . '/bootstrap/database.php';
$runner = new MigrationRunner(
    $bootstrap['connections'],
    new MigrationRegistry($bootstrap['base_path'] . '/database/migrations'),
);
$command = $argv[1] ?? 'up';
try {
    if ($command === 'status') {
        fwrite(STDOUT, "Status\tBatch\tMigration\n");
        foreach ($runner->status() as $status) {
            $batch = $status->batch === null ? '-' : (string) $status->batch;
            fwrite(STDOUT, sprintf("%s\t%s\t%s\n", $status->applied ? 'applied' : 'pending', $batch, $status->name));
        }
        exit(0);
    }
    if ($command === 'up') {
        $completed = $runner->migrate();
    } elseif ($command === 'down') {
        $isProduction = $bootstrap['config']->string('app.env', 'production') === 'production';
        if ($isProduction && !in_array('--force', $argv, true)) {
            throw new RuntimeException('Production rollback requires the explicit --force flag.');
        }
        $steps = isset($argv[2]) && ctype_digit($argv[2]) ? (int) $argv[2] : 1;
        $completed = $runner->rollback($steps);
    } else {
        throw new InvalidArgumentException('Usage: migrate.php [up|down [steps] [--force]|status]');
    }
    if ($completed === []) {
        fwrite(STDOUT, "Nothing to do.\n");
    }
    foreach ($completed as $migration) {
        fwrite(STDOUT, sprintf("%s: %s\n", $command === 'up' ? 'Migrated' : 'Rolled back', $migration));
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: {$exception->getMessage()}\n");
    exit(1);
}
