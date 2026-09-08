<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Content;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Content\ContentSanitizer;
use PerrymanFinance\Domain\Content\PublicationWorkflow;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Services\Seo\SeoService;

final readonly class CmsService
{
    public function __construct(
        private ContentRepository $content,
        private ContentSanitizer $sanitizer,
        private PublicationWorkflow $workflow,
        private TransactionManager $transactions,
        private AuditLogRepository $audit,
        private SeoService $seo,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function pages(): array
    {
        return $this->content->pages();
    }

    /** @return array<string, mixed> */
    public function page(string $key, bool $public = false): array
    {
        $page = $this->content->page($key, $public) ?? throw new NotFoundException();
        if ($public) {
            $page['seo'] = $this->content->seo('page', (int) $page['id']);
            foreach (['id', 'created_by', 'updated_by', 'deleted_at'] as $field) {
                unset($page[$field]);
            }
        }
        return $page;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function savePage(?string $uuid, array $input, int $actor, ?string $requestId): array
    {
        $existing = $uuid === null ? null : $this->page($uuid);
        $title = $this->string($input, 'title', 255);
        $slug = $this->slug($input['slug'] ?? $title);
        $status = $this->status($input['status'] ?? ($existing['status'] ?? 'draft'));
        if ($existing !== null) {
            $this->workflow->guard((string) $existing['status'], $status);
        } elseif ($status !== 'draft') {
            throw new ValidationException(['status' => ['New pages must begin as drafts.']]);
        }
        $sections = $input['sections'] ?? [];
        if (!is_array($sections) || !array_is_list($sections)) {
            throw new ValidationException(['sections' => ['Sections must be a list.']]);
        }
        $sections = $this->sanitizer->sections($sections);
        $now = $this->now();
        $record = [
            'uuid' => $uuid ?? $this->uuid(), 'title' => $title, 'slug' => $slug,
            'type' => $this->string($input, 'page_type', 80), 'status' => $status,
            'excerpt' => $this->optionalString($input, 'excerpt', 2000),
            'content' => json_encode($this->sanitizer->sections($sections), JSON_THROW_ON_ERROR),
            'published' => $status === 'published' ? ($existing['published_at'] ?? $now) : null,
            'actor' => $actor, 'now' => $now,
        ];
        $savedUuid = $record['uuid'];
        $this->transactions->run(function () use ($existing, $record, $sections, $actor, $requestId, $slug): void {
            if ($existing === null) {
                $id = $this->content->insertPage($record);
            } else {
                $id = (int) $existing['id'];
                $this->content->updatePage($id, $record);
                if ($existing['status'] === 'published' && $record['status'] === 'published' && $existing['slug'] !== $slug) {
                    $this->seo->recordRedirect(
                        $this->pagePath((string) $existing['slug']),
                        $this->pagePath($slug),
                        $actor,
                        $record['now'],
                    );
                }
            }
            $this->content->replaceSections($id, $sections, $record['now']);
            $this->audit->record($actor, $existing === null ? 'page.created' : 'page.updated', ['subject_type' => 'page', 'subject_id' => $record['uuid'], 'request_id' => $requestId, 'status' => $record['status']], $record['now']);
        });
        return $this->page((string) $savedUuid);
    }

    public function deletePage(string $uuid, int $actor, ?string $requestId): void
    {
        $page = $this->page($uuid);
        $now = $this->now();
        $this->content->deletePage((int) $page['id'], $actor, $now);
        $this->audit->record($actor, 'page.deleted', ['subject_type' => 'page', 'subject_id' => $uuid, 'request_id' => $requestId], $now);
    }

    /** @return list<array<string, mixed>> */
    public function legalDocuments(): array
    {
        return $this->content->legalDocuments();
    }

    /** @return array<string, mixed> */
    public function legal(string $key, bool $public = false): array
    {
        $legal = $this->content->legal($key, $public) ?? throw new NotFoundException();
        if ($public) {
            $legal['seo'] = $this->content->seo('legal_document', (int) $legal['id']);
            foreach (['id', 'created_by', 'updated_by'] as $field) {
                unset($legal[$field]);
            }
        }
        return $legal;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function saveLegal(?string $uuid, array $input, int $actor, ?string $requestId): array
    {
        $existing = $uuid === null ? null : $this->legal($uuid);
        $status = $this->status($input['status'] ?? ($existing['status'] ?? 'draft'));
        $version = $this->string($input, 'version', 40);
        if ($existing !== null && $version !== (string) $existing['version']) {
            $existing = null;
            $uuid = null;
        }
        if ($existing !== null) {
            $this->workflow->guard((string) $existing['status'], $status);
        } elseif ($status !== 'draft') {
            throw new ValidationException(['status' => ['New legal documents must begin as drafts.']]);
        }
        $now = $this->now();
        $record = [
            'uuid' => $uuid ?? $this->uuid(), 'type' => $this->string($input, 'document_type', 80),
            'title' => $this->string($input, 'title', 255), 'slug' => $this->slug($input['slug'] ?? ''),
            'version' => $version,
            'content' => $this->sanitizer->richText($this->string($input, 'content', 1000000)),
            'effective' => $this->date($input['effective_at'] ?? null), 'status' => $status,
            'published' => $status === 'published' ? ($existing['published_at'] ?? $now) : null,
            'actor' => $actor, 'now' => $now,
        ];
        $this->transactions->run(function () use ($existing, $record, $actor, $requestId, $status, $now): void {
            if ($existing === null) {
                $this->content->insertLegal($record);
            } else {
                $this->content->updateLegal((int) $existing['id'], $record);
                if ($existing['status'] === 'published' && $record['status'] === 'published' && $existing['slug'] !== $record['slug']) {
                    $this->seo->recordRedirect(
                        '/' . (string) $existing['slug'],
                        '/' . (string) $record['slug'],
                        $actor,
                        $now,
                    );
                }
            }
            $this->audit->record($actor, $existing === null ? 'legal.created' : 'legal.updated', ['subject_type' => 'legal_document', 'subject_id' => $record['uuid'], 'request_id' => $requestId, 'version' => $record['version'], 'status' => $status], $now);
        });
        return $this->legal((string) $record['uuid']);
    }

    /** @return array<string, mixed> */
    public function archiveLegal(string $uuid, int $actor, ?string $requestId): array
    {
        $legal = $this->legal($uuid);
        return $this->saveLegal($uuid, [...$legal, 'status' => 'archived'], $actor, $requestId);
    }

    /** @return list<array<string, mixed>> */
    public function settings(bool $public = false): array
    {
        return $this->content->settings($public);
    }

    /** @param array<string, mixed> $input */
    public function saveSetting(array $input, int $actor, ?string $requestId): void
    {
        $key = $this->string($input, 'key', 191);
        if (!preg_match('/^[a-z][a-z0-9_.-]*$/', $key) || !array_key_exists('value', $input)) {
            throw new ValidationException(['key' => ['Use a valid setting key.'], 'value' => ['A value is required.']]);
        }
        $now = $this->now();
        $this->content->upsertSetting(
            $key,
            $this->sanitizer->structuredValue($input['value']),
            ($input['is_public'] ?? false) === true,
            $actor,
            $now,
        );
        $this->audit->record($actor, 'setting.updated', ['subject_type' => 'site_setting', 'subject_id' => $key, 'request_id' => $requestId], $now);
    }

    public function deleteSetting(string $key, int $actor, ?string $requestId): void
    {
        $now = $this->now();
        $this->content->deleteSetting($key);
        $this->audit->record($actor, 'setting.deleted', ['subject_type' => 'site_setting', 'subject_id' => $key, 'request_id' => $requestId], $now);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function saveSeo(string $type, string $uuid, array $input, int $actor, ?string $requestId): array
    {
        $owner = $type === 'page' ? $this->page($uuid) : $this->legal($uuid);
        $robots = $this->string($input, 'robots', 100);
        if (!in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            throw new ValidationException(['robots' => ['Select an allowed robots directive.']]);
        }
        $canonical = $this->optionalString($input, 'canonical_url', 2048);
        if ($canonical !== null && filter_var($canonical, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException(['canonical_url' => ['Enter a valid absolute URL.']]);
        }
        $data = [
            'title' => $this->string($input, 'meta_title', 255),
            'description' => $this->string($input, 'meta_description', 500),
            'canonical' => $canonical, 'robots' => $robots,
            'og' => json_encode($input['open_graph'] ?? null, JSON_THROW_ON_ERROR),
            'social' => isset($input['social_media_id']) ? (int) $input['social_media_id'] : null,
            'now' => $this->now(),
        ];
        $this->content->upsertSeo($type, (int) $owner['id'], $data);
        $this->audit->record($actor, 'seo.updated', ['subject_type' => $type, 'subject_id' => $uuid, 'request_id' => $requestId], $data['now']);
        return $this->content->seo($type, (int) $owner['id']) ?? [];
    }

    /** @return array<string, mixed> */
    public function seo(string $type, string $uuid): array
    {
        $owner = $type === 'page' ? $this->page($uuid) : $this->legal($uuid);
        return $this->content->seo($type, (int) $owner['id']) ?? throw new NotFoundException();
    }

    public function deleteSeo(string $type, string $uuid, int $actor, ?string $requestId): void
    {
        $owner = $type === 'page' ? $this->page($uuid) : $this->legal($uuid);
        $this->content->deleteSeo($type, (int) $owner['id']);
        $this->audit->record($actor, 'seo.deleted', ['subject_type' => $type, 'subject_id' => $uuid, 'request_id' => $requestId], $this->now());
    }

    /** @param array<string, mixed> $input */
    private function string(array $input, string $key, int $max): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
            throw new ValidationException([$key => ["This field is required and may not exceed {$max} characters."]]);
        }
        return trim($value);
    }

    /** @param array<string, mixed> $input */
    private function optionalString(array $input, string $key, int $max): ?string
    {
        $value = $input[$key] ?? null;
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) || mb_strlen($value) > $max) {
            throw new ValidationException([$key => ["This field may not exceed {$max} characters."]]);
        }
        return trim($value);
    }

    private function slug(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ValidationException(['slug' => ['A slug is required.']]);
        }
        $slug = trim(mb_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $value) ?? ''), '-');
        if ($slug === '' || strlen($slug) > 191) {
            throw new ValidationException(['slug' => ['Use a valid slug.']]);
        }
        return $slug;
    }

    private function status(mixed $value): string
    {
        if (!is_string($value) || !in_array($value, ['draft', 'review', 'published', 'archived'], true)) {
            throw new ValidationException(['status' => ['Select a valid workflow status.']]);
        }
        return $value;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new ValidationException(['effective_at' => ['Enter a valid effective date.']]);
        }
        try {
            return (new DateTimeImmutable($value, new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        } catch (\Throwable) {
            throw new ValidationException(['effective_at' => ['Enter a valid effective date.']]);
        }
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function pagePath(string $slug): string
    {
        return $slug === 'home' ? '/' : '/' . $slug;
    }
}
