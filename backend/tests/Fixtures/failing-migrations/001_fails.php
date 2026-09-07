<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '001_fails';
    }
    public function up(PDO $connection): void
    {
        $connection->exec('CREATE TABLE partial_table (id INTEGER PRIMARY KEY)');
        throw new RuntimeException('Expected test failure.');
    }
    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE partial_table');
    }
};
