<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string
    {
        return '202609080001_add_release_hardening_indexes';
    }

    public function up(PDO $connection): void
    {
        $connection->exec('CREATE INDEX idx_pages_sitemap ON pages (status, deleted_at, slug, updated_at)');
        $connection->exec('CREATE INDEX idx_investments_public_filters ON investment_opportunities (status, deleted_at, published_at, featured, risk_classification, category_id)');
        $connection->exec('CREATE INDEX idx_articles_public_filters ON articles (status, deleted_at, published_at, featured, category_id)');
        $connection->exec('CREATE INDEX idx_faqs_public_category ON faqs (status, published_at, category, position)');
        $connection->exec('CREATE INDEX idx_redirects_active_source ON redirects (is_active, source_path)');
    }

    public function down(PDO $connection): void
    {
        foreach ([
            'idx_redirects_active_source' => 'redirects',
            'idx_faqs_public_category' => 'faqs',
            'idx_articles_public_filters' => 'articles',
            'idx_investments_public_filters' => 'investment_opportunities',
            'idx_pages_sitemap' => 'pages',
        ] as $index => $table) {
            $connection->exec("DROP INDEX {$index} ON {$table}");
        }
    }
};
