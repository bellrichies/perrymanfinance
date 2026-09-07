<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PerrymanFinance\Database\Migrations\MigrationRegistry;
use PHPUnit\Framework\TestCase;

final class MigrationRegistryTest extends TestCase
{
    public function testFilesAreLoadedInDeterministicFilenameOrder(): void
    {
        $registry = new MigrationRegistry(dirname(__DIR__, 2) . '/Fixtures/migrations');
        self::assertSame(
            ['001_create_widgets', '002_create_gadgets'],
            array_map(static fn ($migration): string => $migration->name(), $registry->all()),
        );
    }
}
