<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Content\ContentSanitizer;
use PerrymanFinance\Domain\Content\PublicationWorkflow;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Middleware\PermissionMiddleware;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Repositories\MediaRepository;
use PerrymanFinance\Services\Content\CmsService;
use PerrymanFinance\Services\Content\MediaService;
use PerrymanFinance\Services\Seo\SeoService;
use PHPUnit\Framework\TestCase;
use Tests\Support\ApplicationFactory;
use Tests\Support\SqliteConnection;

final class CmsFunctionalityTest extends TestCase
{
    private PDO $pdo;
    private CmsService $cms;
    private SqliteConnection $connection;

    protected function setUp(): void
    {
        $this->connection = SqliteConnection::memory();
        $this->pdo = $this->connection->connection();
        $this->createSchema();
        $this->cms = new CmsService(
            new ContentRepository($this->connection),
            new ContentSanitizer(),
            new PublicationWorkflow(),
            new TransactionManager($this->connection),
            new AuditLogRepository($this->connection),
            new SeoService(new ContentRepository($this->connection), new Config(['app' => ['env' => 'testing', 'frontend_url' => 'https://example.test']])),
        );
    }

    public function testDraftIsHiddenAndPublishedPageIsVisible(): void
    {
        $draft = $this->cms->savePage(null, $this->pageInput('draft'), 1, 'request');
        try {
            $this->cms->page('about', true);
            self::fail('Draft page was publicly visible.');
        } catch (NotFoundException $exception) {
            self::assertSame(404, $exception->status);
        }
        $review = $this->cms->savePage((string) $draft['uuid'], $this->pageInput('review'), 1, null);
        $published = $this->cms->savePage((string) $review['uuid'], $this->pageInput('published'), 1, null);
        self::assertSame('published', $published['status']);
        self::assertSame('About', $this->cms->page('about', true)['title']);
    }

    public function testFuturePublishedPageIsNotVisible(): void
    {
        $this->pdo->exec("INSERT INTO pages (uuid,title,slug,page_type,status,excerpt,content_json,published_at,created_by,updated_by,created_at,updated_at) VALUES ('future','Future','future','marketing','published','Future content',NULL,'2999-01-01 00:00:00.000000',1,1,'2026-09-08 00:00:00.000000','2026-09-08 00:00:00.000000')");

        $this->expectException(NotFoundException::class);
        $this->cms->page('future', true);
    }


    public function testLegalVersionUpdatesAndScriptsAreRemoved(): void
    {
        $legal = $this->cms->saveLegal(null, [
            'document_type' => 'privacy_policy', 'title' => 'Privacy', 'slug' => 'privacy',
            'version' => '1.0', 'content' => '<p>Approved</p><script>alert(1)</script>', 'status' => 'draft',
        ], 1, null);
        $updated = $this->cms->saveLegal((string) $legal['uuid'], [
            'document_type' => 'privacy_policy', 'title' => 'Privacy', 'slug' => 'privacy',
            'version' => '1.1', 'content' => '<p>Updated</p>', 'status' => 'draft',
        ], 1, null);
        self::assertSame('1.1', $updated['version']);
        $statement = $this->pdo->query('SELECT COUNT(*) FROM legal_documents');
        self::assertInstanceOf(\PDOStatement::class, $statement);
        self::assertSame(2, (int) $statement->fetchColumn());
        self::assertStringNotContainsString('<script', (string) $legal['content']);
    }

    public function testSeoMetadataPersistsForPageOwner(): void
    {
        $page = $this->cms->savePage(null, $this->pageInput('draft'), 1, null);
        $seo = $this->cms->saveSeo('page', (string) $page['uuid'], [
            'meta_title' => 'About PerrymanFinance', 'meta_description' => 'Institutional information.',
            'canonical_url' => 'https://example.test/about', 'robots' => 'index,follow',
            'open_graph' => ['title' => 'About'],
        ], 1, null);
        self::assertSame('About PerrymanFinance', $seo['meta_title']);
        self::assertSame('About', $this->cms->seo('page', (string) $page['uuid'])['open_graph']['title']);
    }

    public function testPublishedPageRenameCreatesRedirect(): void
    {
        $draft = $this->cms->savePage(null, $this->pageInput('draft'), 1, null);
        $review = $this->cms->savePage((string) $draft['uuid'], $this->pageInput('review'), 1, null);
        $this->cms->savePage((string) $review['uuid'], $this->pageInput('published'), 1, null);
        $renamed = $this->pageInput('published');
        $renamed['slug'] = 'about-perryman';

        $this->cms->savePage((string) $review['uuid'], $renamed, 1, null);

        $statement = $this->pdo->query("SELECT destination_path,status_code FROM redirects WHERE source_path='/about'");
        self::assertInstanceOf(\PDOStatement::class, $statement);
        $redirect = $statement->fetch();
        self::assertIsArray($redirect);
        self::assertSame('/about-perryman', $redirect['destination_path']);
        self::assertSame(301, (int) $redirect['status_code']);
    }


    public function testInvalidMediaUploadIsRejected(): void
    {
        $service = new MediaService(
            new MediaRepository($this->connection),
            new AuditLogRepository($this->connection),
            new Config(['media' => ['path' => sys_get_temp_dir(), 'max_bytes' => 1000, 'max_dimension' => 100]]),
        );
        $this->expectException(\PerrymanFinance\Http\Exceptions\ValidationException::class);
        $service->upload(['error' => UPLOAD_ERR_NO_FILE], null, 1, null);
    }

    public function testCmsMutationWithoutAuthenticatedPrincipalIsDenied(): void
    {
        $application = ApplicationFactory::create(static function ($router): void {
            $router->post('/api/v1/admin/pages', static fn (): Response => new Response([]), [
                new PermissionMiddleware('pages.create'),
            ]);
        });
        $response = $application->handle(new Request('POST', '/api/v1/admin/pages'));
        self::assertSame(401, $response->status());
        self::assertSame('UNAUTHENTICATED', $response->body()['error']['code']);
    }

    /** @return array<string, mixed> */
    private function pageInput(string $status): array
    {
        return [
            'title' => 'About', 'slug' => 'about', 'page_type' => 'marketing', 'status' => $status,
            'excerpt' => 'About the firm',
            'sections' => [['type' => 'hero', 'content' => ['heading' => '<strong>About</strong>']]],
        ];
    }

    private function createSchema(): void
    {
        foreach (
            [
            'CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT UNIQUE,title TEXT,slug TEXT UNIQUE,page_type TEXT,status TEXT,excerpt TEXT,content_json TEXT,published_at TEXT,created_by INTEGER,updated_by INTEGER,created_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE page_sections (id INTEGER PRIMARY KEY AUTOINCREMENT,page_id INTEGER,section_type TEXT,position INTEGER,content_json TEXT,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE legal_documents (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT UNIQUE,document_type TEXT,title TEXT,slug TEXT,version TEXT,content TEXT,effective_at TEXT,status TEXT,published_at TEXT,created_by INTEGER,updated_by INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE site_settings (id INTEGER PRIMARY KEY AUTOINCREMENT,setting_key TEXT UNIQUE,value_json TEXT,is_public INTEGER,updated_by INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE media_assets (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT UNIQUE,disk TEXT,path TEXT,original_name TEXT,mime_type TEXT,byte_size INTEGER,width INTEGER,height INTEGER,alt_text TEXT,uploaded_by INTEGER,created_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE seo_metadata (id INTEGER PRIMARY KEY AUTOINCREMENT,page_id INTEGER UNIQUE,legal_document_id INTEGER UNIQUE,investment_opportunity_id INTEGER,article_id INTEGER,meta_title TEXT,meta_description TEXT,canonical_url TEXT,robots TEXT,open_graph_json TEXT,social_media_id INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE redirects (id INTEGER PRIMARY KEY AUTOINCREMENT,source_path TEXT UNIQUE,destination_path TEXT,status_code INTEGER,is_active INTEGER,created_by INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT,actor_id INTEGER,event TEXT,subject_type TEXT,subject_id TEXT,request_id TEXT,ip_address TEXT,before_json TEXT,after_json TEXT,created_at TEXT)',
            ] as $sql
        ) {
            $this->pdo->exec($sql);
        }
    }
}
