<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Insights;

use DateTimeImmutable;
use DateTimeZone;
use PDOException;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Content\ContentSanitizer;
use PerrymanFinance\Domain\Content\PublicationWorkflow;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Repositories\InsightRepository;

final readonly class InsightService
{
    public function __construct(
        private InsightRepository $insights,
        private ContentRepository $content,
        private ContentSanitizer $sanitizer,
        private PublicationWorkflow $workflow,
        private TransactionManager $transactions,
        private AuditLogRepository $audit,
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function categories(): array
    {
        return $this->insights->categories();
    }

    /** @return list<array<string,mixed>> */
    public function tags(): array
    {
        return $this->insights->tags();
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveCategory(?int $id, array $input): array
    {
        $record = ['name' => $this->string($input, 'name', 160), 'slug' => $this->slug($input['slug'] ?? $input['name'] ?? ''), 'description' => $this->optional($input, 'description', 2000), 'now' => $this->now()];
        try {
            $id === null ? $id = $this->insights->insertCategory($record) : $this->insights->updateCategory($id, $record);
        } catch (PDOException $exception) {
            if ($this->duplicate($exception)) {
                throw new ValidationException(['slug' => ['Category name and slug must be unique.']]);
            }
            throw $exception;
        }
        return $this->insights->category($id) ?? throw new NotFoundException();
    }

    public function deleteCategory(int $id): void
    {
        if ($this->insights->category($id) === null) {
            throw new NotFoundException();
        }
        try {
            $this->insights->deleteCategory($id);
        } catch (PDOException) {
            throw new ValidationException(['category' => ['A category in use cannot be deleted.']]);
        }
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveTag(?int $id, array $input): array
    {
        $record = ['name' => $this->string($input, 'name', 120), 'slug' => $this->slug($input['slug'] ?? $input['name'] ?? ''), 'now' => $this->now()];
        try {
            $id === null ? $id = $this->insights->insertTag($record) : $this->insights->updateTag($id, $record);
        } catch (PDOException $exception) {
            if ($this->duplicate($exception)) {
                throw new ValidationException(['slug' => ['Tag name and slug must be unique.']]);
            }
            throw $exception;
        }
        return $this->insights->tag($id) ?? throw new NotFoundException();
    }

    public function deleteTag(int $id): void
    {
        if ($this->insights->tag($id) === null) {
            throw new NotFoundException();
        }
        try {
            $this->insights->deleteTag($id);
        } catch (PDOException) {
            throw new ValidationException(['tag' => ['A tag in use cannot be deleted.']]);
        }
    }

    /**
     * @param array<string,mixed> $query
     * @return array{items:list<array<string,mixed>>,meta:array<string,int>}
     */
    public function articles(array $query, bool $public = false): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($query['per_page'] ?? 12)));
        $sortValue = is_string($query['sort'] ?? null) ? $query['sort'] : '-published_at';
        $direction = str_starts_with($sortValue, '-') ? 'desc' : 'asc';
        $sort = ltrim($sortValue, '-');
        if (!in_array($sort, ['published_at', 'title', 'created_at', 'updated_at'], true)) {
            throw new ValidationException(['sort' => ['Select a supported sort field.']]);
        }
        $filters = ['page' => $page, 'per_page' => $perPage, 'sort' => $sort, 'direction' => $direction];
        foreach (['category', 'tag', 'search'] as $key) {
            if (is_string($query[$key] ?? null) && trim($query[$key]) !== '') {
                $filters[$key] = trim($query[$key]);
            }
        }
        if (isset($query['status']) && !$public) {
            $filters['status'] = $this->status($query['status']);
        }
        if (isset($query['featured'])) {
            if (!in_array((string) $query['featured'], ['0', '1'], true)) {
                throw new ValidationException(['featured' => ['Featured must be 0 or 1.']]);
            }
            $filters['featured'] = (int) $query['featured'];
        }
        $result = $this->insights->articles($filters, $public);
        return ['items' => $this->publicShape($result['items'], $public), 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total'], 'total_pages' => (int) ceil($result['total'] / $perPage)]];
    }

    /** @return array<string,mixed> */
    public function article(string $key, bool $public = false): array
    {
        $item = $this->insights->article($key, $public) ?? throw new NotFoundException();
        $item['seo'] = $this->content->seo('article', (int) $item['id']);
        if ($public) {
            $item['related'] = $this->publicShape($this->insights->related($item, 3), true);
            return $this->publicShape([$item], true)[0];
        }
        return $item;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveArticle(?string $uuid, array $input, int $actor, ?string $requestId): array
    {
        $existing = $uuid === null ? null : $this->article($uuid);
        $status = $this->status($input['status'] ?? ($existing['status'] ?? 'draft'));
        if ($existing !== null) {
            $this->workflow->guard((string) $existing['status'], $status);
        } elseif ($status !== 'draft') {
            throw new ValidationException(['status' => ['New articles must begin as drafts.']]);
        }
        $category = (int) ($input['category_id'] ?? 0);
        if ($this->insights->category($category) === null) {
            throw new ValidationException(['category_id' => ['Select a valid category.']]);
        }
        $tagIds = $this->tagIds($input['tag_ids'] ?? []);
        $now = $this->now();
        $record = ['uuid' => $uuid ?? $this->uuid(), 'category' => $category, 'title' => $this->string($input, 'title', 255), 'slug' => $this->slug($input['slug'] ?? $input['title'] ?? ''), 'excerpt' => $this->string($input, 'excerpt', 2000), 'content' => $this->sanitizer->richText($this->string($input, 'content', 1000000)), 'cover' => $this->nullablePositiveInt($input['cover_media_id'] ?? null), 'author' => $actor, 'status' => $status, 'featured' => ($input['featured'] ?? false) ? 1 : 0, 'published' => $status === 'published' ? ($existing['published_at'] ?? $now) : null, 'now' => $now];
        try {
            $this->transactions->run(function () use ($existing, $record, $tagIds, $input, $actor, $requestId): void {
                $id = $existing === null ? $this->insights->insertArticle($record) : (int) $existing['id'];
                if ($existing !== null) {
                    $this->insights->updateArticle($id, $record);
                }
                $this->insights->replaceArticleTags($id, $tagIds, $record['now']);
                if (is_array($input['seo'] ?? null)) {
                    $this->content->upsertSeo('article', $id, $this->seo($input['seo'], $record['now']));
                }
                $this->audit->record($actor, $existing === null ? 'article.created' : 'article.updated', ['subject_type' => 'article', 'subject_id' => $record['uuid'], 'request_id' => $requestId, 'status' => $record['status']], $record['now']);
            });
        } catch (PDOException $exception) {
            if ($this->duplicate($exception)) {
                throw new ValidationException(['slug' => ['Slug must be unique.']]);
            }
            throw $exception;
        }
        return $this->article((string) $record['uuid']);
    }

    /** @return array<string,mixed> */
    public function archive(string $uuid, int $actor, ?string $requestId): array
    {
        $existing = $this->article($uuid);
        $this->workflow->guard((string) $existing['status'], 'archived');
        $now = $this->now();
        $this->insights->archiveArticle((int) $existing['id'], $now);
        $this->audit->record($actor, 'article.archived', ['subject_type' => 'article', 'subject_id' => $uuid, 'request_id' => $requestId], $now);
        return $this->article($uuid);
    }

    /** @return list<array<string,mixed>> */
    public function faqs(bool $public = false, ?string $category = null): array
    {
        $items = $this->insights->faqs($public, $category);
        if (!$public) {
            return $items;
        }
        return array_map(static function (array $faq): array {
            unset($faq['created_by'], $faq['updated_by']);
            return $faq;
        }, $items);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveFaq(?int $id, array $input, int $actor): array
    {
        $existing = $id === null ? null : ($this->insights->faq($id) ?? throw new NotFoundException());
        $status = $this->status($input['status'] ?? ($existing['status'] ?? 'draft'));
        if ($existing !== null) {
            $this->workflow->guard((string) $existing['status'], $status);
        } elseif ($status !== 'draft') {
            throw new ValidationException(['status' => ['New FAQs must begin as drafts.']]);
        }
        $now = $this->now();
        $record = ['question' => $this->string($input, 'question', 500), 'answer' => $this->sanitizer->richText($this->string($input, 'answer', 20000)), 'category' => $this->optional($input, 'category', 120), 'position' => max(0, (int) ($input['position'] ?? 0)), 'status' => $status, 'published' => $status === 'published' ? ($existing['published_at'] ?? $now) : null, 'actor' => $actor, 'now' => $now];
        $id === null ? $id = $this->insights->insertFaq($record) : $this->insights->updateFaq($id, $record);
        return $this->insights->faq($id) ?? throw new NotFoundException();
    }

    public function deleteFaq(int $id): void
    {
        if ($this->insights->faq($id) === null) {
            throw new NotFoundException();
        }
        $this->insights->deleteFaq($id);
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    private function publicShape(array $items, bool $public): array
    {
        if (!$public) {
            return $items;
        }
        return array_map(static function (array $item): array {
            foreach (['id', 'author_id', 'deleted_at'] as $field) {
                unset($item[$field]);
            }
            return $item;
        }, $items);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function seo(array $input, string $now): array
    {
        $robots = (string) ($input['robots'] ?? 'index,follow');
        if (!in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            throw new ValidationException(['seo.robots' => ['Select a valid robots directive.']]);
        }
        return ['title' => $this->string($input, 'meta_title', 255), 'description' => $this->string($input, 'meta_description', 500), 'canonical' => $this->optional($input, 'canonical_url', 2048), 'robots' => $robots, 'og' => json_encode($input['open_graph'] ?? null, JSON_THROW_ON_ERROR), 'social' => $this->nullablePositiveInt($input['social_media_id'] ?? null), 'now' => $now];
    }

    /** @return list<int> */
    private function tagIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $tagId) {
            $id = filter_var($tagId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false || $this->insights->tag((int) $id) === null) {
                throw new ValidationException(['tag_ids' => ['Select valid tags.']]);
            }
            $ids[] = (int) $id;
        }
        return array_values(array_unique($ids));
    }

    /** @param array<string,mixed> $input */
    private function string(array $input, string $key, int $max): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
            throw new ValidationException([$key => ["{$key} is required and must not exceed {$max} characters."]]);
        }
        return trim($value);
    }

    /** @param array<string,mixed> $input */
    private function optional(array $input, string $key, int $max): ?string
    {
        $value = $input[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) || mb_strlen($value) > $max) {
            throw new ValidationException([$key => ["{$key} must not exceed {$max} characters."]]);
        }
        return trim($value);
    }

    private function status(mixed $value): string
    {
        if (!is_string($value) || !in_array($value, ['draft', 'review', 'published', 'archived'], true)) {
            throw new ValidationException(['status' => ['Select a valid workflow status.']]);
        }
        return $value;
    }

    private function slug(mixed $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $value), '-'));
        if ($slug === '' || strlen($slug) > 191) {
            throw new ValidationException(['slug' => ['Provide a valid slug.']]);
        }
        return $slug;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($int === false) {
            throw new ValidationException(['media_id' => ['Select a valid media asset.']]);
        }
        return $int;
    }

    private function duplicate(PDOException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '19'], true);
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
