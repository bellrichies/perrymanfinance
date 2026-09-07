<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Migrations;

use RuntimeException;

final readonly class MigrationRegistry
{
    public function __construct(private string $directory)
    {
    }

    /** @return list<Migration> */
    public function all(): array
    {
        $files = glob(rtrim($this->directory, '/\\') . '/*.php');
        if ($files === false) {
            throw new RuntimeException('Unable to read the migration directory.');
        }
        sort($files, SORT_STRING);
        $migrations = [];
        $names = [];
        foreach ($files as $file) {
            $migration = require $file;
            if (!$migration instanceof Migration) {
                throw new RuntimeException("Migration file {$file} must return a Migration instance.");
            }
            if (isset($names[$migration->name()])) {
                throw new RuntimeException("Duplicate migration name: {$migration->name()}.");
            }
            $names[$migration->name()] = true;
            $migrations[] = $migration;
        }
        return $migrations;
    }
}
