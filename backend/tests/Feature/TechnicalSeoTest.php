<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Services\Seo\SeoService;
use PHPUnit\Framework\TestCase;
use Tests\Support\SqliteConnection;

final class TechnicalSeoTest extends TestCase
{
    private PDO $pdo;
    private SeoService $seo;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->createSchema();
        $this->seo = new SeoService(
            new ContentRepository($connection),
            new Config(['app' => ['env' => 'production', 'frontend_url' => 'https://perryman.test']]),
        );
    }

    public function testSitemapIncludesOnlyPublishedIndexablePublicContent(): void
    {
        $this->seedContent();

        $xml = $this->seo->sitemapXml();

        self::assertStringContainsString('<loc>https://perryman.test/about</loc>', $xml);
        self::assertStringContainsString('<loc>https://perryman.test/investments/core-strategy</loc>', $xml);
        self::assertStringContainsString('<loc>https://canonical.test/legal/risk</loc>', $xml);
        self::assertStringNotContainsString('/draft-page', $xml);
        self::assertStringNotContainsString('/noindex-page', $xml);
        self::assertStringNotContainsString('/admin', $xml);
        self::assertStringNotContainsString('/insights/noindex-insight', $xml);
    }

    public function testRobotsReferencesAbsoluteProductionSitemap(): void
    {
        self::assertSame(
            "User-agent: *\nDisallow: /admin\nDisallow: /api/v1/admin\nSitemap: https://perryman.test/sitemap.xml\n",
            $this->seo->robotsTxt(),
        );
    }

    public function testProductionSitemapRequiresHttpsOrigin(): void
    {
        $service = new SeoService(
            new ContentRepository(SqliteConnection::memory()),
            new Config(['app' => ['env' => 'production', 'frontend_url' => 'http://perryman.test']]),
        );

        $this->expectException(ValidationException::class);
        $service->robotsTxt();
    }

    public function testRedirectsAreInternalPublicPathsOnly(): void
    {
        $now = '2026-09-08 00:00:00.000000';
        $this->seo->recordRedirect('/insights/old-slug', '/insights/new-slug', 1, $now);

        $redirect = $this->seo->redirect('/insights/old-slug');

        self::assertSame('/insights/new-slug', $redirect['destination_path']);
        self::assertSame(301, $redirect['status_code']);

        $this->expectException(ValidationException::class);
        $this->seo->recordRedirect('/admin/pages', '/about', 1, $now);
    }

    private function seedContent(): void
    {
        $now = '2026-09-08 00:00:00.000000';
        $this->pdo->exec("INSERT INTO pages (id,uuid,title,slug,status,published_at,updated_at,deleted_at) VALUES
            (1,'home','Home','home','published','{$now}','{$now}',NULL),
            (2,'about','About','about','published','{$now}','{$now}',NULL),
            (3,'draft','Draft','draft-page','draft',NULL,'{$now}',NULL),
            (4,'noindex','Noindex','noindex-page','published','{$now}','{$now}',NULL)");
        $this->pdo->exec("INSERT INTO legal_documents (id,title,slug,status,effective_at,updated_at) VALUES
            (1,'Risk','risk-disclosure','published','{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO investment_opportunities (id,title,slug,status,published_at,updated_at,deleted_at) VALUES
            (1,'Core Strategy','core-strategy','published','{$now}','{$now}',NULL)");
        $this->pdo->exec("INSERT INTO articles (id,title,slug,status,published_at,updated_at,deleted_at) VALUES
            (1,'Noindex Insight','noindex-insight','published','{$now}','{$now}',NULL)");
        $this->pdo->exec("INSERT INTO seo_metadata (page_id,meta_title,meta_description,robots,canonical_url,open_graph_json,created_at,updated_at) VALUES
            (1,'Home','Home description','index,follow',NULL,NULL,'{$now}','{$now}'),
            (2,'About','About description','index,follow',NULL,NULL,'{$now}','{$now}'),
            (4,'Noindex','Noindex description','noindex,follow',NULL,NULL,'{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO seo_metadata (legal_document_id,meta_title,meta_description,robots,canonical_url,open_graph_json,created_at,updated_at) VALUES
            (1,'Risk','Risk description','index,follow','https://canonical.test/legal/risk',NULL,'{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO seo_metadata (investment_opportunity_id,meta_title,meta_description,robots,canonical_url,open_graph_json,created_at,updated_at) VALUES
            (1,'Core Strategy','Opportunity description','index,follow',NULL,NULL,'{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO seo_metadata (article_id,meta_title,meta_description,robots,canonical_url,open_graph_json,created_at,updated_at) VALUES
            (1,'Noindex Insight','Insight description','noindex,nofollow',NULL,NULL,'{$now}','{$now}')");
    }

    private function createSchema(): void
    {
        foreach ([
            'CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT,title TEXT,slug TEXT,status TEXT,published_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE legal_documents (id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,slug TEXT,status TEXT,effective_at TEXT,updated_at TEXT)',
            'CREATE TABLE investment_opportunities (id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,slug TEXT,status TEXT,published_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,slug TEXT,status TEXT,published_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE faqs (id INTEGER PRIMARY KEY AUTOINCREMENT,question TEXT,answer TEXT,category TEXT,position INTEGER,status TEXT,published_at TEXT,updated_at TEXT)',
            'CREATE TABLE seo_metadata (id INTEGER PRIMARY KEY AUTOINCREMENT,page_id INTEGER,legal_document_id INTEGER,investment_opportunity_id INTEGER,article_id INTEGER,meta_title TEXT,meta_description TEXT,canonical_url TEXT,robots TEXT,open_graph_json TEXT,social_media_id INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE redirects (id INTEGER PRIMARY KEY AUTOINCREMENT,source_path TEXT UNIQUE,destination_path TEXT,status_code INTEGER,is_active INTEGER,created_by INTEGER,created_at TEXT,updated_at TEXT)',
        ] as $sql) {
            $this->pdo->exec($sql);
        }
    }
}
