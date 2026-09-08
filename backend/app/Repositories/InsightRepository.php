<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

use DateTimeImmutable;
use DateTimeZone;

final class InsightRepository extends AbstractRepository
{
    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        return array_values($this->execute('SELECT id,name,slug,description,created_at,updated_at FROM article_categories ORDER BY name')->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function category(int $id): ?array
    {
        $row = $this->execute('SELECT * FROM article_categories WHERE id=:id LIMIT 1', ['id' => $id])->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function insertCategory(array $data): int
    {
        $this->execute('INSERT INTO article_categories (name,slug,description,created_at,updated_at) VALUES (:name,:slug,:description,:now,:now)', $data);
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateCategory(int $id, array $data): void
    {
        $this->execute('UPDATE article_categories SET name=:name,slug=:slug,description=:description,updated_at=:now WHERE id=:id', [...$data, 'id' => $id]);
    }

    public function deleteCategory(int $id): void
    {
        $this->execute('DELETE FROM article_categories WHERE id=:id', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function tags(): array
    {
        return array_values($this->execute('SELECT id,name,slug,created_at,updated_at FROM tags ORDER BY name')->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function tag(int $id): ?array
    {
        $row = $this->execute('SELECT * FROM tags WHERE id=:id LIMIT 1', ['id' => $id])->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function insertTag(array $data): int
    {
        $this->execute('INSERT INTO tags (name,slug,created_at,updated_at) VALUES (:name,:slug,:now,:now)', $data);
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateTag(int $id, array $data): void
    {
        $this->execute('UPDATE tags SET name=:name,slug=:slug,updated_at=:now WHERE id=:id', [...$data, 'id' => $id]);
    }

    public function deleteTag(int $id): void
    {
        $this->execute('DELETE FROM tags WHERE id=:id', ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function articles(array $filters, bool $public): array
    {
        $where = ['a.deleted_at IS NULL'];
        $params = [];
        if ($public) {
            $where[] = "a.status='published'";
            $where[] = 'a.published_at IS NOT NULL';
            $where[] = 'a.published_at <= :now';
            $params['now'] = $this->now();
        } elseif (isset($filters['status'])) {
            $where[] = 'a.status=:status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['category'])) {
            $where[] = 'c.slug=:category';
            $params['category'] = $filters['category'];
        }
        if (isset($filters['tag'])) {
            $where[] = 'EXISTS (SELECT 1 FROM article_tags atf JOIN tags tf ON tf.id=atf.tag_id WHERE atf.article_id=a.id AND tf.slug=:tag)';
            $params['tag'] = $filters['tag'];
        }
        if (isset($filters['featured'])) {
            $where[] = 'a.featured=:featured';
            $params['featured'] = $filters['featured'];
        }
        if (isset($filters['search'])) {
            $where[] = '(a.title LIKE :search OR a.excerpt LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        $clause = implode(' AND ', $where);
        $total = (int) $this->execute("SELECT COUNT(*) FROM articles a JOIN article_categories c ON c.id=a.category_id WHERE {$clause}", $params)->fetchColumn();
        $sort = $this->allowedIdentifier((string) $filters['sort'], ['published_at', 'title', 'created_at', 'updated_at']);
        $direction = $filters['direction'] === 'asc' ? 'ASC' : 'DESC';
        $limit = (int) $filters['per_page'];
        $offset = ((int) $filters['page'] - 1) * $limit;
        $sql = 'SELECT a.*,c.name AS category_name,c.slug AS category_slug,u.display_name AS author,'
            . 'm.uuid AS cover_media_uuid,m.path AS cover_media_path,m.alt_text AS cover_media_alt '
            . 'FROM articles a JOIN article_categories c ON c.id=a.category_id JOIN admin_users u ON u.id=a.author_id '
            . 'LEFT JOIN media_assets m ON m.id=a.cover_media_id AND m.deleted_at IS NULL '
            . "WHERE {$clause} ORDER BY a.{$sort} {$direction},a.id DESC LIMIT {$limit} OFFSET {$offset}";
        return ['items' => $this->withTags(array_values($this->execute($sql, $params)->fetchAll())), 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function article(string $key, bool $public = false): ?array
    {
        $where = $public ? "a.slug=:key AND a.status='published' AND a.published_at IS NOT NULL AND a.published_at<=:now" : 'a.uuid=:key';
        $params = $public ? ['key' => $key, 'now' => $this->now()] : ['key' => $key];
        $row = $this->execute(
            'SELECT a.*,c.name AS category_name,c.slug AS category_slug,u.display_name AS author,'
            . 'm.uuid AS cover_media_uuid,m.path AS cover_media_path,m.alt_text AS cover_media_alt '
            . 'FROM articles a JOIN article_categories c ON c.id=a.category_id JOIN admin_users u ON u.id=a.author_id '
            . 'LEFT JOIN media_assets m ON m.id=a.cover_media_id AND m.deleted_at IS NULL '
            . "WHERE {$where} AND a.deleted_at IS NULL LIMIT 1",
            $params,
        )->fetch();
        return is_array($row) ? $this->withTags([$row])[0] : null;
    }

    /** @param array<string, mixed> $data */
    public function insertArticle(array $data): int
    {
        $this->execute(
            'INSERT INTO articles (uuid,category_id,title,slug,excerpt,content,cover_media_id,author_id,status,featured,published_at,created_at,updated_at) '
            . 'VALUES (:uuid,:category,:title,:slug,:excerpt,:content,:cover,:author,:status,:featured,:published,:now,:now)',
            $data,
        );
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateArticle(int $id, array $data): void
    {
        unset($data['uuid']);
        $this->execute(
            'UPDATE articles SET category_id=:category,title=:title,slug=:slug,excerpt=:excerpt,content=:content,cover_media_id=:cover,'
            . 'author_id=:author,status=:status,featured=:featured,published_at=:published,updated_at=:now WHERE id=:id',
            [...$data, 'id' => $id],
        );
    }

    /** @param list<int> $tagIds */
    public function replaceArticleTags(int $articleId, array $tagIds, string $now): void
    {
        $this->execute('DELETE FROM article_tags WHERE article_id=:id', ['id' => $articleId]);
        foreach (array_values(array_unique($tagIds)) as $tagId) {
            $this->execute('INSERT INTO article_tags (article_id,tag_id,created_at) VALUES (:article,:tag,:now)', ['article' => $articleId, 'tag' => $tagId, 'now' => $now]);
        }
    }

    public function archiveArticle(int $id, string $now): void
    {
        $this->execute("UPDATE articles SET status='archived',updated_at=:now WHERE id=:id", ['id' => $id, 'now' => $now]);
    }

    /**
     * @param array<string,mixed> $article
     * @return list<array<string,mixed>>
     */
    public function related(array $article, int $limit): array
    {
        $rows = $this->execute(
            "SELECT DISTINCT a.*,c.name AS category_name,c.slug AS category_slug,u.display_name AS author,"
            . "m.uuid AS cover_media_uuid,m.path AS cover_media_path,m.alt_text AS cover_media_alt "
            . "FROM articles a JOIN article_categories c ON c.id=a.category_id JOIN admin_users u ON u.id=a.author_id "
            . "LEFT JOIN media_assets m ON m.id=a.cover_media_id AND m.deleted_at IS NULL "
            . "LEFT JOIN article_tags at ON at.article_id=a.id "
            . "WHERE a.id<>:id AND a.deleted_at IS NULL AND a.status='published' AND a.published_at IS NOT NULL AND a.published_at<=:now "
            . "AND (a.category_id=:category OR at.tag_id IN (SELECT tag_id FROM article_tags WHERE article_id=:id)) "
            . "ORDER BY a.published_at DESC,a.id DESC LIMIT {$limit}",
            ['id' => (int) $article['id'], 'category' => (int) $article['category_id'], 'now' => $this->now()],
        )->fetchAll();
        return $this->withTags(array_values($rows));
    }

    /** @return list<array<string,mixed>> */
    public function faqs(bool $public = false, ?string $category = null): array
    {
        $where = [];
        $params = [];
        if ($public) {
            $where[] = "status='published'";
            $where[] = '(published_at IS NULL OR published_at<=:now)';
            $params['now'] = $this->now();
        }
        if ($category !== null && $category !== '') {
            $where[] = 'category=:category';
            $params['category'] = $category;
        }
        $sql = 'SELECT * FROM faqs' . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where)) . ' ORDER BY position, id';
        return array_values($this->execute($sql, $params)->fetchAll());
    }

    /** @return array<string,mixed>|null */
    public function faq(int $id): ?array
    {
        $row = $this->execute('SELECT * FROM faqs WHERE id=:id LIMIT 1', ['id' => $id])->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function insertFaq(array $data): int
    {
        $this->execute('INSERT INTO faqs (question,answer,category,position,status,published_at,created_by,updated_by,created_at,updated_at) VALUES (:question,:answer,:category,:position,:status,:published,:actor,:actor,:now,:now)', $data);
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function updateFaq(int $id, array $data): void
    {
        $this->execute('UPDATE faqs SET question=:question,answer=:answer,category=:category,position=:position,status=:status,published_at=:published,updated_by=:actor,updated_at=:now WHERE id=:id', [...$data, 'id' => $id]);
    }

    public function deleteFaq(int $id): void
    {
        $this->execute("UPDATE faqs SET status='archived',updated_at=:now WHERE id=:id", ['id' => $id, 'now' => $this->now()]);
    }

    /**
     * @param list<array<string,mixed>> $articles
     * @return list<array<string,mixed>>
     */
    private function withTags(array $articles): array
    {
        if ($articles === []) {
            return [];
        }
        $ids = array_map(static fn (array $article): int => (int) $article['id'], $articles);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $tags = $this->execute(
            "SELECT at.article_id,t.id,t.name,t.slug FROM article_tags at JOIN tags t ON t.id=at.tag_id WHERE at.article_id IN ({$placeholders}) ORDER BY t.name",
            $ids,
        )->fetchAll();
        $byArticle = [];
        foreach ($tags as $tag) {
            $byArticle[(int) $tag['article_id']][] = ['id' => (int) $tag['id'], 'name' => $tag['name'], 'slug' => $tag['slug']];
        }
        foreach ($articles as &$article) {
            $article['tags'] = $byArticle[(int) $article['id']] ?? [];
        }
        return $articles;
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }
}
