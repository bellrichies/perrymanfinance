<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '202609070007_create_rate_limit_counters';
    }

    public function up(PDO $connection): void
    {
        $connection->exec("CREATE TABLE rate_limit_counters (
            counter_key CHAR(64) PRIMARY KEY,
            attempts SMALLINT UNSIGNED NOT NULL,
            window_started_at DATETIME(6) NOT NULL,
            expires_at DATETIME(6) NOT NULL,
            KEY idx_rate_limit_expiry (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS rate_limit_counters');
    }
};
