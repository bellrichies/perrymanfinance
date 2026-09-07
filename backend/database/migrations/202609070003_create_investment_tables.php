<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string { return '202609070003_create_investment_tables'; }

    public function up(PDO $connection): void
    {
        $connection->exec("CREATE TABLE investment_categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(191) NOT NULL,
            description TEXT NULL, position INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_investment_categories_name (name), UNIQUE KEY uq_investment_categories_slug (slug),
            KEY idx_investment_categories_position (position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE investment_opportunities (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uuid CHAR(36) NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(191) NOT NULL,
            short_description TEXT NOT NULL, full_description LONGTEXT NOT NULL,
            strategy_summary TEXT NULL, investment_objective TEXT NULL, investment_horizon VARCHAR(120) NULL,
            risk_classification VARCHAR(80) NOT NULL, minimum_investment_display VARCHAR(120) NULL,
            currency_display VARCHAR(40) NULL,
            status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
            featured TINYINT(1) NOT NULL DEFAULT 0, cover_media_id BIGINT UNSIGNED NULL,
            disclaimer TEXT NOT NULL, published_at DATETIME(6) NULL,
            created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, deleted_at DATETIME(6) NULL,
            UNIQUE KEY uq_investments_uuid (uuid), UNIQUE KEY uq_investments_slug (slug),
            KEY idx_investments_public (status, published_at), KEY idx_investments_category (category_id),
            KEY idx_investments_featured (featured, status), KEY idx_investments_cover (cover_media_id),
            CONSTRAINT fk_investments_category FOREIGN KEY (category_id) REFERENCES investment_categories(id) ON DELETE RESTRICT,
            CONSTRAINT fk_investments_cover FOREIGN KEY (cover_media_id) REFERENCES media_assets(id) ON DELETE SET NULL,
            CONSTRAINT fk_investments_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
            CONSTRAINT fk_investments_updater FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS investment_opportunities');
        $connection->exec('DROP TABLE IF EXISTS investment_categories');
    }
};
