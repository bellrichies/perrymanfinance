<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string { return '202609070006_create_seo_tables'; }
    public function up(PDO $connection): void
    {
        $connection->exec("CREATE TABLE seo_metadata (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id BIGINT UNSIGNED NULL, legal_document_id BIGINT UNSIGNED NULL,
            investment_opportunity_id BIGINT UNSIGNED NULL, article_id BIGINT UNSIGNED NULL,
            meta_title VARCHAR(255) NOT NULL, meta_description VARCHAR(500) NOT NULL,
            canonical_url VARCHAR(2048) NULL, robots VARCHAR(100) NOT NULL DEFAULT 'index,follow',
            open_graph_json JSON NULL, social_media_id BIGINT UNSIGNED NULL,
            created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_seo_page (page_id), UNIQUE KEY uq_seo_legal (legal_document_id),
            UNIQUE KEY uq_seo_investment (investment_opportunity_id), UNIQUE KEY uq_seo_article (article_id),
            KEY idx_seo_social_media (social_media_id),
            CONSTRAINT fk_seo_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
            CONSTRAINT fk_seo_legal FOREIGN KEY (legal_document_id) REFERENCES legal_documents(id) ON DELETE CASCADE,
            CONSTRAINT fk_seo_investment FOREIGN KEY (investment_opportunity_id) REFERENCES investment_opportunities(id) ON DELETE CASCADE,
            CONSTRAINT fk_seo_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
            CONSTRAINT fk_seo_social_media FOREIGN KEY (social_media_id) REFERENCES media_assets(id) ON DELETE SET NULL,
            CONSTRAINT chk_seo_single_owner CHECK (
                (page_id IS NOT NULL) + (legal_document_id IS NOT NULL) +
                (investment_opportunity_id IS NOT NULL) + (article_id IS NOT NULL) = 1
            )
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $connection->exec("CREATE TABLE redirects (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, source_path VARCHAR(500) NOT NULL,
            destination_path VARCHAR(2048) NOT NULL, status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
            is_active TINYINT(1) NOT NULL DEFAULT 1, created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
            UNIQUE KEY uq_redirects_source (source_path), KEY idx_redirects_active (is_active),
            CONSTRAINT fk_redirects_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
            CONSTRAINT chk_redirect_status CHECK (status_code IN (301, 302, 307, 308))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS redirects');
        $connection->exec('DROP TABLE IF EXISTS seo_metadata');
    }
};
