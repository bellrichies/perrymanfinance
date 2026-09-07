<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Seeders;

use PDO;

interface Seeder
{
    public function name(): string;
    public function run(PDO $connection): void;
}
