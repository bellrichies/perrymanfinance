<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '202609130002_create_client_reporting_operations';
    }

    public function up(PDO $connection): void
    {
        $connection->exec("ALTER TABLE client_investment_accounts
            ADD COLUMN current_balance DECIMAL(18,2) NULL AFTER approved_amount,
            ADD COLUMN last_snapshot_at DATETIME(6) NULL AFTER approved_at");
        $connection->exec("CREATE TABLE client_balance_adjustments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL,
            client_investment_account_id BIGINT UNSIGNED NOT NULL,
            adjustment_type ENUM('initial_allocation','increase','decrease','correction','valuation_update') NOT NULL,
            amount DECIMAL(18,2) NOT NULL,
            currency CHAR(3) NOT NULL,
            effective_at DATETIME(6) NOT NULL,
            source_reference VARCHAR(255) NOT NULL,
            reason TEXT NOT NULL,
            idempotency_key VARCHAR(128) NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_adjustments_uuid (uuid),
            UNIQUE KEY uq_client_adjustments_idempotency (client_investment_account_id, created_by, idempotency_key),
            KEY idx_client_adjustments_account (client_investment_account_id, effective_at),
            CONSTRAINT fk_client_adjustments_account FOREIGN KEY (client_investment_account_id) REFERENCES client_investment_accounts(id) ON DELETE RESTRICT,
            CONSTRAINT fk_client_adjustments_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_reporting_snapshots (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL,
            client_investment_account_id BIGINT UNSIGNED NOT NULL,
            snapshot_date DATE NOT NULL,
            principal_amount DECIMAL(18,2) NOT NULL,
            reported_value DECIMAL(18,2) NOT NULL,
            growth_amount DECIMAL(18,2) NOT NULL,
            growth_percent DECIMAL(9,4) NOT NULL,
            currency CHAR(3) NOT NULL,
            methodology_note TEXT NOT NULL,
            source_reference VARCHAR(255) NOT NULL,
            idempotency_key VARCHAR(128) NOT NULL,
            approved_by BIGINT UNSIGNED NOT NULL,
            approved_at DATETIME(6) NOT NULL,
            created_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_snapshots_uuid (uuid),
            UNIQUE KEY uq_client_snapshots_idempotency (client_investment_account_id, approved_by, idempotency_key),
            KEY idx_client_snapshots_account (client_investment_account_id, snapshot_date),
            CONSTRAINT fk_client_snapshots_account FOREIGN KEY (client_investment_account_id) REFERENCES client_investment_accounts(id) ON DELETE RESTRICT,
            CONSTRAINT fk_client_snapshots_admin FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach (['client_balances.adjust','client_reports.publish'] as $permission) {
            $quoted = $connection->quote($permission);
            $label = $connection->quote(ucwords(str_replace(['.', '_'], ' ', $permission)));
            $now = $connection->quote(gmdate('Y-m-d H:i:s.u'));
            $connection->exec("INSERT INTO permissions (name,label,created_at,updated_at) SELECT {$quoted},{$label},{$now},{$now} WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name={$quoted})");
        }
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS client_reporting_snapshots');
        $connection->exec('DROP TABLE IF EXISTS client_balance_adjustments');
        $connection->exec('ALTER TABLE client_investment_accounts DROP COLUMN last_snapshot_at');
        $connection->exec('ALTER TABLE client_investment_accounts DROP COLUMN current_balance');
    }
};
