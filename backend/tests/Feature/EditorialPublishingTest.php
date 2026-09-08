<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Content\ContentSanitizer;
use PerrymanFinance\Domain\Content\PublicationWorkflow;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Repositories\InsightRepository;
use PerrymanFinance\Services\Insights\InsightService;
use PHPUnit\Framework\TestCase;
use Tests\Support\SqliteConnection;

final class EditorialPublishingTest extends TestCase
{
    private PDO $pdo;
    private InsightService $service;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->createSchema();
        $this->service = new InsightService(new InsightRepository($connection), new ContentRepository($connection), new ContentSanitizer(), new PublicationWorkflow(), new TransactionManager($connection), new AuditLogRepository($connection));
        $this->pdo->exec("INSERT INTO admin_users (id,display_name) VALUES (1,'Editorial Admin')");
        $this->service->saveCategory(null, ['name' => 'Research', 'slug' => 'research']);
        $this->service->saveCategory(null, ['name' => 'Operations', 'slug' => 'operations']);
        $this->service->saveTag(null, ['name' => 'Bitcoin', 'slug' => 'bitcoin']);
        $this->service->saveTag(null, ['name' => 'Risk', 'slug' => 'risk']);
    }

    public function testDraftIsInvisibleAndPublishedArticleSanitizesContent(): void
    {
        $draft = $this->service->saveArticle(null, $this->articleInput('draft', 'custody-notes'), 1, null);
        self::assertSame(0, $this->service->articles([], true)['meta']['total']);
        try {
            $this->service->article('custody-notes', true);
            self::fail('Draft article was public.');
        } catch (NotFoundException $exception) {
            self::assertSame(404, $exception->status);
        }
        $review = $this->service->saveArticle((string) $draft['uuid'], $this->articleInput('review', 'custody-notes'), 1, null);
        $published = $this->service->saveArticle((string) $review['uuid'], $this->articleInput('published', 'custody-notes'), 1, null);
        $public = $this->service->article('custody-notes', true);
        self::assertSame('Custody Notes', $public['title']);
        self::assertStringNotContainsString('<script>', (string) $public['content']);
        self::assertNotNull($published['published_at']);
    }

    public function testPaginationFiltersAndArticleTagRelationships(): void
    {
        foreach ([['Alpha', 'alpha', 1, [1]], ['Beta', 'beta', 2, [2]], ['Gamma', 'gamma', 1, [1, 2]]] as [$title, $slug, $category, $tags]) {
            $draft = $this->service->saveArticle(null, $this->articleInput('draft', $slug, $title, $category, $tags), 1, null);
            $review = $this->service->saveArticle((string) $draft['uuid'], $this->articleInput('review', $slug, $title, $category, $tags), 1, null);
            $this->service->saveArticle((string) $review['uuid'], $this->articleInput('published', $slug, $title, $category, $tags), 1, null);
        }
        $result = $this->service->articles(['category' => 'research', 'tag' => 'bitcoin', 'page' => 1, 'per_page' => 1, 'sort' => 'title'], true);
        self::assertSame(2, $result['meta']['total']);
        self::assertSame(2, $result['meta']['total_pages']);
        self::assertSame('Alpha', $result['items'][0]['title']);
        self::assertSame('Bitcoin', $result['items'][0]['tags'][0]['name']);
    }

    public function testRelatedArticlesUseCategoryOrSharedTags(): void
    {
        foreach ([['Primary', 'primary', 1, [1]], ['Same Category', 'same-category', 1, [2]], ['Shared Tag', 'shared-tag', 2, [1]]] as [$title, $slug, $category, $tags]) {
            $draft = $this->service->saveArticle(null, $this->articleInput('draft', $slug, $title, $category, $tags), 1, null);
            $review = $this->service->saveArticle((string) $draft['uuid'], $this->articleInput('review', $slug, $title, $category, $tags), 1, null);
            $this->service->saveArticle((string) $review['uuid'], $this->articleInput('published', $slug, $title, $category, $tags), 1, null);
        }
        $detail = $this->service->article('primary', true);
        $titles = array_column($detail['related'], 'title');
        self::assertContains('Same Category', $titles);
        self::assertContains('Shared Tag', $titles);
    }

    public function testFaqOrderingAndPublicationVisibility(): void
    {
        $this->service->saveFaq(null, ['question' => 'Second?', 'answer' => '<p>Second.</p>', 'position' => 20, 'status' => 'draft'], 1);
        $first = $this->service->saveFaq(null, ['question' => 'First?', 'answer' => '<p>First.</p><script>bad()</script>', 'position' => 10, 'status' => 'draft'], 1);
        $this->service->saveFaq((int) $first['id'], ['question' => 'First?', 'answer' => '<p>First.</p><script>bad()</script>', 'position' => 10, 'status' => 'review'], 1);
        $this->service->saveFaq((int) $first['id'], ['question' => 'First?', 'answer' => '<p>First.</p><script>bad()</script>', 'position' => 10, 'status' => 'published'], 1);
        $public = $this->service->faqs(true);
        self::assertCount(1, $public);
        self::assertSame('First?', $public[0]['question']);
        self::assertStringNotContainsString('<script>', (string) $public[0]['answer']);
    }

    /**
     * @param list<int> $tags
     * @return array<string,mixed>
     */
    private function articleInput(string $status, string $slug, string $title = 'Custody Notes', int $category = 1, array $tags = [1]): array
    {
        return ['category_id' => $category, 'title' => $title, 'slug' => $slug, 'excerpt' => 'A concise editorial summary.', 'content' => '<p>Reviewed insight.</p><script>bad()</script>', 'tag_ids' => $tags, 'status' => $status, 'featured' => false, 'seo' => ['meta_title' => $title, 'meta_description' => 'Editorial insight.', 'robots' => 'index,follow']];
    }

    private function createSchema(): void
    {
        $statements = [
            'CREATE TABLE admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT,display_name TEXT)',
            'CREATE TABLE article_categories (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT UNIQUE,slug TEXT UNIQUE,description TEXT,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE media_assets (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT,path TEXT,alt_text TEXT,deleted_at TEXT)',
            'CREATE TABLE articles (id INTEGER PRIMARY KEY AUTOINCREMENT,uuid TEXT UNIQUE,category_id INTEGER,title TEXT,slug TEXT UNIQUE,excerpt TEXT,content TEXT,cover_media_id INTEGER,author_id INTEGER,status TEXT,featured INTEGER,published_at TEXT,created_at TEXT,updated_at TEXT,deleted_at TEXT)',
            'CREATE TABLE tags (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT UNIQUE,slug TEXT UNIQUE,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE article_tags (article_id INTEGER,tag_id INTEGER,created_at TEXT,PRIMARY KEY (article_id, tag_id))',
            'CREATE TABLE faqs (id INTEGER PRIMARY KEY AUTOINCREMENT,question TEXT,answer TEXT,category TEXT,position INTEGER,status TEXT,published_at TEXT,created_by INTEGER,updated_by INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE seo_metadata (id INTEGER PRIMARY KEY AUTOINCREMENT,page_id INTEGER,legal_document_id INTEGER,investment_opportunity_id INTEGER,article_id INTEGER UNIQUE,meta_title TEXT,meta_description TEXT,canonical_url TEXT,robots TEXT,open_graph_json TEXT,social_media_id INTEGER,created_at TEXT,updated_at TEXT)',
            'CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT,actor_id INTEGER,event TEXT,subject_type TEXT,subject_id TEXT,request_id TEXT,ip_address TEXT,before_json TEXT,after_json TEXT,created_at TEXT)',
        ];
        foreach ($statements as $sql) {
            $this->pdo->exec($sql);
        }
    }
}
