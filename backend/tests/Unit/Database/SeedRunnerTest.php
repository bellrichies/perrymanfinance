<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PerrymanFinance\Database\Seeders\SeedRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\SqliteConnection;

final class SeedRunnerTest extends TestCase
{
    public function testProductionSeedingIsRejected(): void
    {
        $runner = new SeedRunner(SqliteConnection::memory(), [], 'production');
        $this->expectException(RuntimeException::class);
        $runner->run();
    }
}
