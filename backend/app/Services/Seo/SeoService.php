<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Seo;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\ContentRepository;

final readonly class SeoService
{
    public function __construct(private ContentRepository $content, private Config $config)
    {
    }

    public function sitemapXml(): string
    {
        $items = array_map(
            fn (array $entry): string => $this->sitemapUrl($entry),
            $this->sitemapEntries(),
        );

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . implode('', $items)
            . '</urlset>';
    }

    public function robotsTxt(): string
    {
        return "User-agent: *\n"
            . "Disallow: /admin\n"
            . "Disallow: /api/v1/admin\n"
            . "Sitemap: " . $this->origin() . "/sitemap.xml\n";
    }

    /** @return list<array<string, mixed>> */
    public function sitemapEntries(): array
    {
        $entries = [];
        foreach ($this->content->sitemapEntries($this->now()) as $entry) {
            if (!is_string($entry['meta_title'] ?? null) || trim((string) $entry['meta_title']) === '') {
                continue;
            }
            if (!is_string($entry['meta_description'] ?? null) || trim((string) $entry['meta_description']) === '') {
                continue;
            }
            if (str_contains((string) ($entry['robots'] ?? 'index,follow'), 'noindex')) {
                continue;
            }

            $path = $this->pathFor((string) $entry['type'], (string) $entry['slug']);
            $entries[] = [
                'type' => $entry['type'],
                'title' => $entry['title'],
                'path' => $path,
                'loc' => $this->canonical($entry, $path),
                'lastmod' => substr((string) $entry['updated_at'], 0, 10),
            ];
        }

        usort($entries, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));
        return $entries;
    }

    /** @return array<string, mixed> */
    public function redirect(string $sourcePath): array
    {
        $path = $this->internalPath($sourcePath);
        $redirect = $this->content->redirect($path) ?? throw new NotFoundException();

        return [
            'source_path' => $redirect['source_path'],
            'destination_path' => $redirect['destination_path'],
            'status_code' => (int) $redirect['status_code'],
        ];
    }

    public function recordRedirect(string $sourcePath, string $destinationPath, int $actor, string $now): void
    {
        $source = $this->internalPath($sourcePath);
        $destination = $this->internalPath($destinationPath);
        if ($source === $destination) {
            return;
        }

        $this->content->retargetRedirectDestinations($source, $destination, $now);
        $this->content->upsertRedirect($source, $destination, 301, $actor, $now);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function sitemapUrl(array $entry): string
    {
        return '<url><loc>' . $this->xml((string) $entry['loc']) . '</loc>'
            . '<lastmod>' . $this->xml((string) $entry['lastmod']) . '</lastmod></url>';
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function canonical(array $entry, string $path): string
    {
        $canonical = $entry['canonical_url'] ?? null;
        if (is_string($canonical) && $canonical !== '') {
            if (filter_var($canonical, FILTER_VALIDATE_URL) === false) {
                throw new ValidationException(['canonical_url' => ['Canonical URLs must be absolute.']]);
            }

            return $canonical;
        }

        return $this->origin() . $path;
    }

    private function origin(): string
    {
        $origin = rtrim($this->config->string('app.frontend_url', $this->config->string('app.url')), '/');
        if (filter_var($origin, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException(['site_url' => ['Configure an absolute public site URL.']]);
        }

        $parts = parse_url($origin);
        if (!is_array($parts)) {
            throw new ValidationException(['site_url' => ['Configure an absolute public site URL.']]);
        }
        if (($parts['path'] ?? '') !== '' || ($parts['query'] ?? '') !== '' || ($parts['fragment'] ?? '') !== '') {
            throw new ValidationException(['site_url' => ['Configure the public site URL as an origin only.']]);
        }

        if ($this->config->string('app.env', 'production') === 'production' && ($parts['scheme'] ?? '') !== 'https') {
            throw new ValidationException(['site_url' => ['Production canonical URLs require an HTTPS public origin.']]);
        }

        return $origin;
    }

    private function pathFor(string $type, string $slug): string
    {
        return match ($type) {
            'page' => $slug === 'home' ? '/' : '/' . $slug,
            'legal_document' => '/' . $slug,
            'investment_opportunity' => '/investments/' . $slug,
            'article' => '/insights/' . $slug,
            default => throw new ValidationException(['type' => ['Unsupported sitemap entry type.']]),
        };
    }

    private function internalPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw new ValidationException(['path' => ['Use an internal public path.']]);
        }
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/api/v1/admin')) {
            throw new ValidationException(['path' => ['Admin paths cannot be redirected through public SEO.']]);
        }

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }
}
