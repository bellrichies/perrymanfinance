<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string { return '202609070002_create_content_tables'; }

    public function up(PDO $connection): void
    {
        $statements = [
            "CREATE TABLE media_assets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uuid CHAR(36) NOT NULL,
                disk VARCHAR(40) NOT NULL DEFAULT 'local', path VARCHAR(500) NOT NULL,
                original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL,
                byte_size BIGINT UNSIGNED NOT NULL, width INT UNSIGNED NULL, height INT UNSIGNED NULL,
                alt_text VARCHAR(255) NULL, uploaded_by BIGINT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, deleted_at DATETIME(6) NULL,
                UNIQUE KEY uq_media_uuid (uuid), UNIQUE KEY uq_media_disk_path (disk, path),
                KEY idx_media_uploader_created (uploaded_by, created_at),
                CONSTRAINT fk_media_uploader FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE pages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uuid CHAR(36) NOT NULL,
                title VARCHAR(255) NOT NULL, slug VARCHAR(191) NOT NULL, page_type VARCHAR(80) NOT NULL,
                status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
                excerpt TEXT NULL, content_json JSON NULL, published_at DATETIME(6) NULL,
                created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, deleted_at DATETIME(6) NULL,
                UNIQUE KEY uq_pages_uuid (uuid), UNIQUE KEY uq_pages_slug (slug),
                KEY idx_pages_public (status, published_at),
                CONSTRAINT fk_pages_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
                CONSTRAINT fk_pages_updater FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE page_sections (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, page_id BIGINT UNSIGNED NOT NULL,
                section_type VARCHAR(80) NOT NULL, position INT UNSIGNED NOT NULL,
                content_json JSON NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_page_sections_position (page_id, position),
                KEY idx_page_sections_type (section_type),
                CONSTRAINT fk_page_sections_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE legal_documents (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uuid CHAR(36) NOT NULL,
                document_type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(191) NOT NULL,
                version VARCHAR(40) NOT NULL, content LONGTEXT NOT NULL, effective_at DATETIME(6) NULL,
                status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
                published_at DATETIME(6) NULL, created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_legal_uuid (uuid), UNIQUE KEY uq_legal_slug_version (slug, version),
                KEY idx_legal_public (slug, status, effective_at),
                CONSTRAINT fk_legal_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
                CONSTRAINT fk_legal_updater FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE site_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(191) NOT NULL,
                value_json JSON NOT NULL, is_public TINYINT(1) NOT NULL DEFAULT 0,
                updated_by BIGINT UNSIGNED NOT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_site_settings_key (setting_key), KEY idx_site_settings_public (is_public),
                CONSTRAINT fk_site_settings_updater FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($statements as $statement) { $connection->exec($statement); }
    }

    public function down(PDO $connection): void
    {
        foreach (['site_settings', 'legal_documents', 'page_sections', 'pages', 'media_assets'] as $table) {
            $connection->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
};
