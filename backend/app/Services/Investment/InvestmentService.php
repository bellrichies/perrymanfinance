<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Investment;

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
use PerrymanFinance\Repositories\InvestmentRepository;

final readonly class InvestmentService
{
    private const RISKS = ['low', 'moderate', 'high', 'very_high'];

    public function __construct(
        private InvestmentRepository $investments,
        private ContentRepository $content,
        private ContentSanitizer $sanitizer,
        private PublicationWorkflow $workflow,
        private TransactionManager $transactions,
        private AuditLogRepository $audit,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        return $this->investments->categories();
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveCategory(?int $id, array $input): array
    {
        $record = ['name' => $this->string($input, 'name', 160), 'slug' => $this->slug($input['slug'] ?? $input['name'] ?? ''), 'description' => $this->optional($input, 'description', 2000), 'position' => max(0, (int) ($input['position'] ?? 0)), 'now' => $this->now()];
        try {
            $id === null ? $id = $this->investments->insertCategory($record) : $this->investments->updateCategory($id, $record);
        } catch (PDOException $exception) {
            if ($this->duplicate($exception)) {
                throw new ValidationException(['slug' => ['Category name and slug must be unique.']]);
            }
            throw $exception;
        }
        return $this->investments->category($id) ?? throw new NotFoundException();
    }

    public function deleteCategory(int $id): void
    {
        if ($this->investments->category($id) === null) {
            throw new NotFoundException();
        }
        try {
            $this->investments->deleteCategory($id);
        } catch (PDOException) {
            throw new ValidationException(['category' => ['A category in use cannot be deleted.']]);
        }
    }

    /**
     * @param array<string,mixed> $query
     * @return array{items:list<array<string,mixed>>,meta:array<string,int>}
     */
    public function opportunities(array $query, bool $public = false): array
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
        foreach (['category', 'search'] as $key) {
            if (is_string($query[$key] ?? null) && trim($query[$key]) !== '') {
                $filters[$key] = trim($query[$key]);
            }
        }
        if (isset($query['risk'])) {
            $risk = (string) $query['risk'];
            if (!in_array($risk, self::RISKS, true)) {
                throw new ValidationException(['risk' => ['Select a valid risk classification.']]);
            } $filters['risk'] = $risk;
        }
        if (isset($query['status']) && !$public) {
            $status = $this->status($query['status']);
            $filters['status'] = $status;
        }
        if (isset($query['featured'])) {
            if (!in_array((string) $query['featured'], ['0', '1'], true)) {
                throw new ValidationException(['featured' => ['Featured must be 0 or 1.']]);
            } $filters['featured'] = (int) $query['featured'];
        }
        $result = $this->investments->opportunities($filters, $public);
        return ['items' => $result['items'], 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total'], 'total_pages' => (int) ceil($result['total'] / $perPage)]];
    }

    /** @return array<string,mixed> */
    public function opportunity(string $key, bool $public = false): array
    {
        $item = $this->investments->opportunity($key, $public) ?? throw new NotFoundException();
        $item['seo'] = $this->content->seo('investment_opportunity', (int) $item['id']);
        if ($public) {
            foreach (['id', 'created_by', 'updated_by', 'deleted_at'] as $field) {
                unset($item[$field]);
            }
        }
        return $item;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function saveOpportunity(?string $uuid, array $input, int $actor, ?string $requestId): array
    {
        $existing = $uuid === null ? null : $this->opportunity($uuid);
        $status = $this->status($input['status'] ?? ($existing['status'] ?? 'draft'));
        if ($existing !== null) {
            $this->workflow->guard((string) $existing['status'], $status);
        } elseif ($status !== 'draft') {
            throw new ValidationException(['status' => ['New opportunities must begin as drafts.']]);
        }
        $risk = (string) ($input['risk_classification'] ?? '');
        if (!in_array($risk, self::RISKS, true)) {
            throw new ValidationException(['risk_classification' => ['Select low, moderate, high, or very_high.']]);
        }
        $category = (int) ($input['category_id'] ?? 0);
        if ($this->investments->category($category) === null) {
            throw new ValidationException(['category_id' => ['Select a valid category.']]);
        }
        $disclaimer = $this->string($input, 'disclaimer', 10000);
        if ($status === 'published' && trim($disclaimer) === '') {
            throw new ValidationException(['disclaimer' => ['A disclaimer is required before publication.']]);
        }
        $now = $this->now();
        $record = ['uuid' => $uuid ?? $this->uuid(), 'category' => $category, 'title' => $this->string($input, 'title', 255), 'slug' => $this->slug($input['slug'] ?? $input['title'] ?? ''), 'short' => $this->string($input, 'short_description', 2000), 'full' => $this->sanitizer->richText($this->string($input, 'full_description', 1000000)), 'strategy' => $this->optionalRich($input, 'strategy_summary'), 'objective' => $this->optionalRich($input, 'investment_objective'), 'horizon' => $this->optional($input, 'investment_horizon', 120), 'risk' => $risk, 'minimum' => $this->optional($input, 'minimum_investment_display', 120), 'currency' => $this->optional($input, 'currency_display', 40), 'status' => $status, 'featured' => ($input['featured'] ?? false) ? 1 : 0, 'cover' => $this->nullablePositiveInt($input['cover_media_id'] ?? null), 'disclaimer' => $this->sanitizer->richText($disclaimer), 'published' => $status === 'published' ? ($existing['published_at'] ?? $now) : null, 'actor' => $actor, 'now' => $now];
        try {
            $this->transactions->run(function () use ($existing, $record, $input, $actor, $requestId): void {
                $id = $existing === null ? $this->investments->insertOpportunity($record) : (int) $existing['id'];
                if ($existing !== null) {
                    $this->investments->updateOpportunity($id, $record);
                }
                if (is_array($input['seo'] ?? null)) {
                    $this->content->upsertSeo('investment_opportunity', $id, $this->seo($input['seo'], $record['now']));
                }
                $this->audit->record($actor, $existing === null ? 'investment.created' : 'investment.updated', ['subject_type' => 'investment_opportunity', 'subject_id' => $record['uuid'], 'request_id' => $requestId, 'status' => $record['status']], $record['now']);
            });
        } catch (PDOException $exception) {
            if ($this->duplicate($exception)) {
                throw new ValidationException(['slug' => ['Slug must be unique.']]);
            }
            throw $exception;
        }
        return $this->opportunity((string) $record['uuid']);
    }

    /** @return array<string,mixed> */
    public function archive(string $uuid, int $actor, ?string $requestId): array
    {
        $existing = $this->opportunity($uuid);
        $this->workflow->guard((string) $existing['status'], 'archived');
        $now = $this->now();
        $this->investments->archiveOpportunity((int) $existing['id'], $actor, $now);
        $this->audit->record($actor, 'investment.archived', ['subject_type' => 'investment_opportunity', 'subject_id' => $uuid, 'request_id' => $requestId], $now);
        return $this->opportunity($uuid);
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
    /** @param array<string,mixed> $input */ private function string(array $input, string $key, int $max): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
            throw new ValidationException([$key => ["{$key} is required and must not exceed {$max} characters."]]);
        } return trim($value);
    }
    /** @param array<string,mixed> $input */ private function optional(array $input, string $key, int $max): ?string
    {
        $value = $input[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        } if (!is_string($value) || mb_strlen($value) > $max) {
            throw new ValidationException([$key => ["{$key} must not exceed {$max} characters."]]);
        } return trim($value);
    }
    /** @param array<string,mixed> $input */ private function optionalRich(array $input, string $key): ?string
    {
        $value = $this->optional($input, $key, 1000000);
        return $value === null ? null : $this->sanitizer->richText($value);
    }
    private function status(mixed $value): string
    {
        if (!is_string($value) || !in_array($value, ['draft', 'review', 'published', 'archived'], true)) {
            throw new ValidationException(['status' => ['Select a valid workflow status.']]);
        } return $value;
    }
    private function slug(mixed $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $value), '-'));
        if ($slug === '' || strlen($slug) > 191) {
            throw new ValidationException(['slug' => ['Provide a valid slug.']]);
        } return $slug;
    }
    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        } $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($int === false) {
            throw new ValidationException(['cover_media_id' => ['Select a valid media asset.']]);
        } return $int;
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
