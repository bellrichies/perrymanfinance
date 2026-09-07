<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Migrations;

use PDO;

interface Migration
{
    public function name(): string;
    public function up(PDO $connection): void;
    public function down(PDO $connection): void;
}
