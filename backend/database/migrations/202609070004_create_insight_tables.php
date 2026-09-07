<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string { return '202609070004_create_insight_tables'; }

    public function up(PDO $connection): void
    {
        $statements = [
            "CREATE TABLE article_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(191) NOT NULL,
                description TEXT NULL, created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_article_categories_name (name), UNIQUE KEY uq_article_categories_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE articles (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uuid CHAR(36) NOT NULL,
                category_id BIGINT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(191) NOT NULL,
                excerpt TEXT NOT NULL, content LONGTEXT NOT NULL, cover_media_id BIGINT UNSIGNED NULL,
                author_id BIGINT UNSIGNED NOT NULL,
                status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
                featured TINYINT(1) NOT NULL DEFAULT 0, published_at DATETIME(6) NULL,
                created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, deleted_at DATETIME(6) NULL,
                UNIQUE KEY uq_articles_uuid (uuid), UNIQUE KEY uq_articles_slug (slug),
                KEY idx_articles_public (status, published_at), KEY idx_articles_category (category_id),
                KEY idx_articles_author (author_id), KEY idx_articles_cover (cover_media_id),
                KEY idx_articles_featured (featured, status),
                CONSTRAINT fk_articles_category FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE RESTRICT,
                CONSTRAINT fk_articles_cover FOREIGN KEY (cover_media_id) REFERENCES media_assets(id) ON DELETE SET NULL,
                CONSTRAINT fk_articles_author FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE tags (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(191) NOT NULL,
                created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
                UNIQUE KEY uq_tags_name (name), UNIQUE KEY uq_tags_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE article_tags (
                article_id BIGINT UNSIGNED NOT NULL, tag_id BIGINT UNSIGNED NOT NULL, created_at DATETIME(6) NOT NULL,
                PRIMARY KEY (article_id, tag_id), KEY idx_article_tags_tag (tag_id),
                CONSTRAINT fk_article_tags_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                CONSTRAINT fk_article_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE faqs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, question VARCHAR(500) NOT NULL, answer TEXT NOT NULL,
                category VARCHAR(120) NULL, position INT UNSIGNED NOT NULL DEFAULT 0,
                status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
                published_at DATETIME(6) NULL, created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NOT NULL,
                created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL,
                KEY idx_faqs_public (status, position), KEY idx_faqs_category (category),
                CONSTRAINT fk_faqs_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
                CONSTRAINT fk_faqs_updater FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($statements as $statement) { $connection->exec($statement); }
    }

    public function down(PDO $connection): void
    {
        foreach (['faqs', 'article_tags', 'tags', 'articles', 'article_categories'] as $table) {
            $connection->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
};
