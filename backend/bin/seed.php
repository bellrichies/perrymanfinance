<?php

declare(strict_types=1);

use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\ConnectionInterface;
use PerrymanFinance\Database\Seeders\ReferenceDataSeeder;
use PerrymanFinance\Database\Seeders\SeedRunner;

/** @var array{base_path: string, config: Config, connections: ConnectionInterface} $bootstrap */
$bootstrap = require dirname(__DIR__) . '/bootstrap/database.php';
$runner = new SeedRunner(
    $bootstrap['connections'],
    [new ReferenceDataSeeder()],
    $bootstrap['config']->string('app.env', 'production'),
);
try {
    foreach ($runner->run() as $seeder) {
        fwrite(STDOUT, "Seeded: {$seeder}\n");
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "Seeding failed: {$exception->getMessage()}\n");
    exit(1);
}
