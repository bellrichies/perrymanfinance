<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Content\ContentSanitizer;
use PerrymanFinance\Domain\Content\PublicationWorkflow;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Http\Middleware\PermissionMiddleware;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Repositories\InvestmentRepository;
use PerrymanFinance\Services\Investment\InvestmentService;
use PerrymanFinance\Services\Seo\SeoService;
use PHPUnit\Framework\TestCase;
use Tests\Support\ApplicationFactory;
use Tests\Support\SqliteConnection;

final class InvestmentCatalogueTest extends TestCase
{
    private PDO $pdo;
    private InvestmentService $service;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->createSchema();
        $content = new ContentRepository($connection);
        $this->service = new InvestmentService(
            new InvestmentRepository($connection),
            $content,
            new ContentSanitizer(),
            new PublicationWorkflow(),
            new TransactionManager($connection),
            new AuditLogRepository($connection),
            new SeoService($content, new Config(['app' => ['env' => 'testing', 'frontend_url' => 'https://example.test']])),
        );
        $this->service->saveCategory(null, ['name' => 'Diversified', 'slug' => 'diversified']);
    }

    public function testDraftIsInvisibleAndPublishedDetailIsPublic(): void
    {
        $draft = $this->service->saveOpportunity(null, $this->input('draft', 'core-strategy'), 1, null);
        self::assertSame(0, $this->service->opportunities([], true)['meta']['total']);
        try {
            $this->service->opportunity('core-strategy', true);
            self::fail('Draft was public.');
        } catch (NotFoundException $exception) {
            self::assertSame(404, $exception->status);
        }
        $review = $this->service->saveOpportunity((string) $draft['uuid'], $this->input('review', 'core-strategy'), 1, null);
        $published = $this->service->saveOpportunity((string) $review['uuid'], $this->input('published', 'core-strategy'), 1, null);
        self::assertSame('Core Strategy', $this->service->opportunity('core-strategy', true)['title']);
        self::assertNotNull($published['published_at']);
    }

    public function testFilteringAndPagination(): void
    {
        foreach ([['Alpha', 'alpha', 'moderate'], ['Beta', 'beta', 'high'], ['Gamma', 'gamma', 'moderate']] as [$title, $slug, $risk]) {
            $draft = $this->service->saveOpportunity(null, $this->input('draft', $slug, $title, $risk), 1, null);
            $review = $this->service->saveOpportunity((string) $draft['uuid'], $this->input('review', $slug, $title, $risk), 1, null);
            $this->service->saveOpportunity((string) $review['uuid'], $this->input('published', $slug, $title, $risk), 1, null);
        }
        $result = $this->service->opportunities(['risk' => 'moderate', 'page' => 1, 'per_page' => 1, 'sort' => 'title'], true);
        self::assertSame(2, $result['meta']['total']);
        self::assertSame(2, $result['meta']['total_pages']);
        self::assertSame('Alpha', $result['items'][0]['title']);
    }

    public function testSlugAndRiskValidation(): void
    {
        $this->service->saveOpportunity(null, $this->input('draft', 'unique'), 1, null);
        try {
            $this->service->saveOpportunity(null, $this->input('draft', 'unique'), 1, null);
            self::fail('Duplicate accepted.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('slug', $exception->fields);
        }
        $invalid = $this->input('draft', 'invalid');
        $invalid['risk_classification'] = 'guaranteed';
        try {
            $this->service->saveOpportunity(null, $invalid, 1, null);
            self::fail('Invalid risk accepted.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('risk_classification', $exception->fields);
        }
    }

    public function testAdminMutationRequiresInvestmentPermission(): void
    {
        $application = ApplicationFactory::create(static function ($router): void {
            $router->post(
                '/api/v1/admin/investments',
                static fn (): Response => new Response([]),
                [new PermissionMiddleware('investments.create')],
            );
        });

        $response = $application->handle(new Request('POST', '/api/v1/admin/investments'));

        self::assertSame(401, $response->status());
        self::assertSame('UNAUTHENTICATED', $response->body()['error']['code']);
    }

    /** @return array<string,mixed> */
    private function input(string $status, string $slug, string $title = 'Core Strategy', string $risk = 'moderate'): array
    {
        return ['category_id' => 1, 'title' => $title, 'slug' => $slug, 'short_description' => 'A factual summary.', 'full_description' => '<p>Reviewed information.</p><script>bad()</script>', 'strategy_summary' => 'Diversified approach.', 'investment_objective' => 'Long-term objective.', 'investment_horizon' => '3–5 years', 'risk_classification' => $risk, 'status' => $status, 'featured' => false, 'disclaimer' => '<p>Capital is at risk.</p>', 'seo' => ['meta_title' => $title, 'meta_description' => 'Opportunity information.', 'robots' => 'index,follow']];
    }

    private function createSchema(): void
    {
        $statements = [
            'CREATE TABLE investment_categories (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT UNIQUE,slug TEXT UNIQUE,description TEXT,position INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE media_assets (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT,path TEXT,alt_text TEXT,deleted_at TEXT)',
            'CREATE TABLE investment_opportunities (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT UNIQUE,category_id INTEGER,title TEXT,slug TEXT UNIQUE,short_description TEXT,full_description TEXT,strategy_summary TEXT,investment_objective TEXT,investment_horizon TEXT,risk_classification TEXT,minimum_investment_display TEXT,currency_display TEXT,status TEXT,featured INTEGER,cover_media_id INTEGER,disclaimer TEXT,published_at TEXT,created_by INTEGER,updated_by INTEGER,created_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE seo_metadata (id INTEGER PRIMARY KEY AUTOINCREMENT,page_id INTEGER,legal_document_id INTEGER,investment_opportunity_id INTEGER UNIQUE,article_id INTEGER,meta_title TEXT,meta_description TEXT,canonical_url TEXT,robots TEXT,open_graph_json TEXT,social_media_id INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE redirects (id INTEGER PRIMARY KEY AUTOINCREMENT,source_path TEXT UNIQUE,destination_path TEXT,status_code INTEGER,is_active INTEGER,created_by INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT,actor_id INTEGER,event TEXT,subject_type TEXT,subject_id TEXT,request_id TEXT,ip_address TEXT,before_json TEXT,after_json TEXT,created_at TEXT)',
        ];
        foreach ($statements as $sql) {
            $this->pdo->exec($sql);
        }
    }
}
