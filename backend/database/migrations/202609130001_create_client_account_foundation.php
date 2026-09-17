<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '202609130001_create_client_account_foundation';
    }

    public function up(PDO $connection): void
    {
        $connection->exec("CREATE TABLE client_users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL,
            email VARCHAR(254) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('pending_verification','active','suspended','closed') NOT NULL DEFAULT 'pending_verification',
            email_verified_at DATETIME(6) NULL,
            last_login_at DATETIME(6) NULL,
            created_at DATETIME(6) NOT NULL,
            updated_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_users_uuid (uuid),
            UNIQUE KEY uq_client_users_email (email),
            KEY idx_client_users_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_profiles (
            client_user_id BIGINT UNSIGNED PRIMARY KEY,
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) NOT NULL,
            phone VARCHAR(40) NULL,
            country VARCHAR(80) NULL,
            consent_marketing TINYINT(1) NOT NULL DEFAULT 0,
            consent_terms_at DATETIME(6) NOT NULL,
            created_at DATETIME(6) NOT NULL,
            updated_at DATETIME(6) NOT NULL,
            CONSTRAINT fk_client_profiles_user FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_email_verification_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_user_id BIGINT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME(6) NOT NULL,
            used_at DATETIME(6) NULL,
            created_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_verify_hash (token_hash),
            KEY idx_client_verify_user (client_user_id, expires_at),
            CONSTRAINT fk_client_verify_user FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_password_reset_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_user_id BIGINT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME(6) NOT NULL,
            used_at DATETIME(6) NULL,
            created_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_reset_hash (token_hash),
            KEY idx_client_reset_user (client_user_id, expires_at),
            CONSTRAINT fk_client_reset_user FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_sessions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_user_id BIGINT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME(6) NOT NULL,
            revoked_at DATETIME(6) NULL,
            replaced_by_id BIGINT UNSIGNED NULL,
            created_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_session_hash (token_hash),
            KEY idx_client_session_user (client_user_id, expires_at),
            CONSTRAINT fk_client_session_user FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_session_replacement FOREIGN KEY (replaced_by_id) REFERENCES client_sessions(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_plan_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL,
            client_user_id BIGINT UNSIGNED NOT NULL,
            investment_opportunity_id BIGINT UNSIGNED NOT NULL,
            requested_amount DECIMAL(18,2) NULL,
            currency CHAR(3) NOT NULL,
            status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
            risk_acknowledged_at DATETIME(6) NOT NULL,
            client_note TEXT NULL,
            reviewed_by BIGINT UNSIGNED NULL,
            reviewed_at DATETIME(6) NULL,
            admin_note TEXT NULL,
            created_at DATETIME(6) NOT NULL,
            updated_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_plan_requests_uuid (uuid),
            KEY idx_client_plan_requests_client (client_user_id, status),
            KEY idx_client_plan_requests_status (status, created_at),
            CONSTRAINT fk_client_plan_requests_client FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_plan_requests_opportunity FOREIGN KEY (investment_opportunity_id) REFERENCES investment_opportunities(id) ON DELETE RESTRICT,
            CONSTRAINT fk_client_plan_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_investment_accounts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL,
            client_user_id BIGINT UNSIGNED NOT NULL,
            investment_opportunity_id BIGINT UNSIGNED NOT NULL,
            plan_request_id BIGINT UNSIGNED NOT NULL,
            status ENUM('active','paused','closed') NOT NULL DEFAULT 'active',
            approved_amount DECIMAL(18,2) NULL,
            currency CHAR(3) NOT NULL,
            approved_by BIGINT UNSIGNED NOT NULL,
            approved_at DATETIME(6) NOT NULL,
            created_at DATETIME(6) NOT NULL,
            updated_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_client_accounts_uuid (uuid),
            UNIQUE KEY uq_client_accounts_request (plan_request_id),
            KEY idx_client_accounts_client (client_user_id, status),
            CONSTRAINT fk_client_accounts_client FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
            CONSTRAINT fk_client_accounts_opportunity FOREIGN KEY (investment_opportunity_id) REFERENCES investment_opportunities(id) ON DELETE RESTRICT,
            CONSTRAINT fk_client_accounts_request FOREIGN KEY (plan_request_id) REFERENCES client_plan_requests(id) ON DELETE RESTRICT,
            CONSTRAINT fk_client_accounts_approver FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE client_audit_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor_type ENUM('client','admin','system') NOT NULL,
            actor_id BIGINT UNSIGNED NULL,
            client_user_id BIGINT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            entity_type VARCHAR(120) NULL,
            entity_id VARCHAR(64) NULL,
            old_values_json JSON NULL,
            new_values_json JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            request_id VARCHAR(128) NULL,
            created_at DATETIME(6) NOT NULL,
            KEY idx_client_audit_client (client_user_id, created_at),
            KEY idx_client_audit_action (action, created_at),
            CONSTRAINT fk_client_audit_client FOREIGN KEY (client_user_id) REFERENCES client_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach (['clients.view','clients.update_status','client_plans.review','client_audit.view'] as $permission) {
            $quoted = $connection->quote($permission);
            $label = $connection->quote(ucwords(str_replace(['.', '_'], ' ', $permission)));
            $now = $connection->quote(gmdate('Y-m-d H:i:s.u'));
            $connection->exec("INSERT INTO permissions (name,label,created_at,updated_at) SELECT {$quoted},{$label},{$now},{$now} WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name={$quoted})");
        }
    }

    public function down(PDO $connection): void
    {
        foreach (['client_audit_events','client_investment_accounts','client_plan_requests','client_sessions','client_password_reset_tokens','client_email_verification_tokens','client_profiles','client_users'] as $table) {
            $connection->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
};
