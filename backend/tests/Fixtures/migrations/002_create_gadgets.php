<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '002_create_gadgets';
    }
    public function up(PDO $connection): void
    {
        $connection->exec('CREATE TABLE gadgets (id INTEGER PRIMARY KEY)');
    }
    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE gadgets');
    }
};
