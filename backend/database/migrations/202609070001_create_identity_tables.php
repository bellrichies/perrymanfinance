<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '202609070001_create_identity_tables';
    }

    public function up(PDO $connection): void
    {
        $statements = [
            "CREATE TABLE admin_users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uuid CHAR(36) NOT NULL,
                email VARCHAR(254) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(120) NOT NULL,
                status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
                last_login_at DATETIME(6) NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                deleted_at DATETIME(6) NULL,
                UNIQUE KEY uq_admin_users_uuid (uuid),
                UNIQUE KEY uq_admin_users_email (email),
                KEY idx_admin_users_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE roles (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                label VARCHAR(120) NOT NULL,
                description VARCHAR(500) NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_roles_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE permissions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                label VARCHAR(160) NOT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_permissions_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE role_permissions (
                role_id BIGINT UNSIGNED NOT NULL,
                permission_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL,
                PRIMARY KEY (role_id, permission_id),
                CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE user_roles (
                admin_user_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL,
                PRIMARY KEY (admin_user_id, role_id),
                KEY idx_user_roles_role (role_id),
                CONSTRAINT fk_user_roles_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE password_reset_tokens (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_user_id BIGINT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME(6) NOT NULL,
                used_at DATETIME(6) NULL,
                created_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_password_reset_token_hash (token_hash),
                KEY idx_password_reset_user_expires (admin_user_id, expires_at),
                CONSTRAINT fk_password_reset_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE refresh_tokens (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_user_id BIGINT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME(6) NOT NULL,
                revoked_at DATETIME(6) NULL,
                replaced_by_id BIGINT UNSIGNED NULL,
                created_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_refresh_token_hash (token_hash),
                KEY idx_refresh_user_expires (admin_user_id, expires_at),
                KEY idx_refresh_replaced_by (replaced_by_id),
                CONSTRAINT fk_refresh_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
                CONSTRAINT fk_refresh_replacement FOREIGN KEY (replaced_by_id) REFERENCES refresh_tokens(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                actor_id BIGINT UNSIGNED NULL,
                event VARCHAR(120) NOT NULL,
                subject_type VARCHAR(120) NULL,
                subject_id VARCHAR(64) NULL,
                request_id VARCHAR(128) NULL,
                ip_address VARCHAR(45) NULL,
                before_json JSON NULL,
                after_json JSON NULL,
                created_at DATETIME(6) NOT NULL,
                KEY idx_audit_actor_created (actor_id, created_at),
                KEY idx_audit_subject (subject_type, subject_id),
                KEY idx_audit_event_created (event, created_at),
                CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES admin_users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }
    }

    public function down(PDO $connection): void
    {
        foreach (['audit_logs', 'refresh_tokens', 'password_reset_tokens', 'user_roles', 'role_permissions', 'permissions', 'roles', 'admin_users'] as $table) {
            $connection->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
};
