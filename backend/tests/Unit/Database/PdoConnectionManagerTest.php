<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\PdoConnectionManager;
use PHPUnit\Framework\TestCase;

final class PdoConnectionManagerTest extends TestCase
{
    public function testConfiguredConnectionIsCreatedOnceWithSafeDefaults(): void
    {
        $calls = 0;
        $manager = new PdoConnectionManager(
            new Config(['database' => ['dsn' => 'sqlite::memory:', 'username' => '', 'password' => '']]),
            static function (string $dsn, string $username, string $password, array $options) use (&$calls): PDO {
                $calls++;
                self::assertSame('sqlite::memory:', $dsn);
                self::assertFalse($options[PDO::ATTR_EMULATE_PREPARES]);
                return new PDO($dsn);
            }
        );
        self::assertSame($manager->connection(), $manager->connection());
        self::assertSame(1, $calls);
    }
}
