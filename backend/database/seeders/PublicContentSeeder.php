<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Seeders;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class PublicContentSeeder implements Seeder
{
    public function name(): string
    {
        return 'public_pages_sections_settings_and_seo';
    }

    public function run(PDO $connection): void
    {
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        $actor = $this->ensureSeederAdmin($connection, $now);
        foreach ($this->pages() as $page) {
            $pageId = $this->upsertPage($connection, $page, $actor, $now);
            $this->replaceSections($connection, $pageId, $page['sections'], $now);
            $this->upsertSeo($connection, 'page_id', $pageId, $page['seo'], $now);
        }
        foreach ($this->legalDocuments() as $document) {
            $documentId = $this->upsertLegalDocument($connection, $document, $actor, $now);
            $this->upsertSeo($connection, 'legal_document_id', $documentId, $document['seo'], $now);
        }
        foreach ($this->settings() as $key => $value) {
            $statement = $connection->prepare(
                'INSERT INTO site_settings (setting_key,value_json,is_public,updated_by,created_at,updated_at)
                VALUES (:key,:value,1,:actor,:now,:now)
                ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),is_public=VALUES(is_public),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)',
            );
            $statement->execute(['key' => $key, 'value' => json_encode($value, JSON_THROW_ON_ERROR), 'actor' => $actor, 'now' => $now]);
        }
    }

    private function ensureSeederAdmin(PDO $connection, string $now): int
    {
        $query = $connection->query("SELECT id FROM admin_users WHERE email='seed-content-admin@example.test' LIMIT 1");
        $existing = $query === false ? false : $query->fetchColumn();
        if ($existing !== false) {
            return (int) $existing;
        }

        $statement = $connection->prepare(
            'INSERT INTO admin_users (uuid,email,password_hash,display_name,status,created_at,updated_at)
            VALUES (:uuid,:email,:password,:name,:status,:now,:now)',
        );
        $statement->execute([
            'uuid' => $this->uuid('seed-content-admin'),
            'email' => 'seed-content-admin@example.test',
            'password' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
            'name' => 'Content Seeder',
            'status' => 'inactive',
            'now' => $now,
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $page
     */
    private function upsertPage(PDO $connection, array $page, int $actor, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO pages (uuid,title,slug,page_type,status,excerpt,content_json,published_at,created_by,updated_by,created_at,updated_at)
            VALUES (:uuid,:title,:slug,:type,:status,:excerpt,:content,:published,:actor,:actor,:now,:now)
            ON DUPLICATE KEY UPDATE title=VALUES(title),page_type=VALUES(page_type),status=VALUES(status),excerpt=VALUES(excerpt),content_json=VALUES(content_json),published_at=VALUES(published_at),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at),deleted_at=NULL',
        );
        $statement->execute([
            'uuid' => $page['uuid'],
            'title' => $page['title'],
            'slug' => $page['slug'],
            'type' => $page['page_type'],
            'status' => 'published',
            'excerpt' => $page['excerpt'],
            'content' => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
            'published' => $now,
            'actor' => $actor,
            'now' => $now,
        ]);

        return $this->idForSlug($connection, 'pages', (string) $page['slug']);
    }

    /**
     * @param list<array{type:string,content:array<string,mixed>}> $sections
     */
    private function replaceSections(PDO $connection, int $pageId, array $sections, string $now): void
    {
        $delete = $connection->prepare('DELETE FROM page_sections WHERE page_id=:page');
        $delete->execute(['page' => $pageId]);
        $insert = $connection->prepare(
            'INSERT INTO page_sections (page_id,section_type,position,content_json,created_at,updated_at)
            VALUES (:page,:type,:position,:content,:now,:now)',
        );
        foreach ($sections as $position => $section) {
            $insert->execute([
                'page' => $pageId,
                'type' => $section['type'],
                'position' => $position,
                'content' => json_encode($section['content'], JSON_THROW_ON_ERROR),
                'now' => $now,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $document
     */
    private function upsertLegalDocument(PDO $connection, array $document, int $actor, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO legal_documents (uuid,document_type,title,slug,version,content,effective_at,status,published_at,created_by,updated_by,created_at,updated_at)
            VALUES (:uuid,:type,:title,:slug,:version,:content,:effective,:status,:published,:actor,:actor,:now,:now)
            ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content),effective_at=VALUES(effective_at),status=VALUES(status),published_at=VALUES(published_at),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)',
        );
        $statement->execute([
            'uuid' => $document['uuid'],
            'type' => $document['document_type'],
            'title' => $document['title'],
            'slug' => $document['slug'],
            'version' => $document['version'],
            'content' => $document['content'],
            'effective' => $now,
            'status' => 'published',
            'published' => $now,
            'actor' => $actor,
            'now' => $now,
        ]);

        return $this->idForSlug($connection, 'legal_documents', (string) $document['slug']);
    }

    /**
     * @param array{title:string,description:string,robots:string,open_graph?:array<string,mixed>} $seo
     */
    private function upsertSeo(PDO $connection, string $ownerColumn, int $ownerId, array $seo, string $now): void
    {
        $insertColumns = "{$ownerColumn},meta_title,meta_description,canonical_url,robots,open_graph_json,social_media_id,created_at,updated_at";
        $statement = $connection->prepare(
            "INSERT INTO seo_metadata ({$insertColumns})
            VALUES (:owner,:title,:description,NULL,:robots,:open_graph,NULL,:now,:now)
            ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),robots=VALUES(robots),open_graph_json=VALUES(open_graph_json),updated_at=VALUES(updated_at)",
        );
        $statement->execute([
            'owner' => $ownerId,
            'title' => $seo['title'],
            'description' => $seo['description'],
            'robots' => $seo['robots'],
            'open_graph' => json_encode($seo['open_graph'] ?? null, JSON_THROW_ON_ERROR),
            'now' => $now,
        ]);
    }

    private function idForSlug(PDO $connection, string $table, string $slug): int
    {
        $statement = $connection->prepare("SELECT id FROM {$table} WHERE slug=:slug ORDER BY id DESC LIMIT 1");
        $statement->execute(['slug' => $slug]);
        return (int) $statement->fetchColumn();
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return [
            'risk_statement' => '<p>Digital asset and wealth-management information is provided for education and enquiry only. Capital is at risk and content requires business and legal review before production use.</p>',
            'contact_details' => '<p>Use the enquiry form to request information from the PerrymanFinance team.</p>',
            'enquiry_consent' => 'I consent to PerrymanFinance using my details to respond to this enquiry.',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function legalDocuments(): array
    {
        $placeholder = '<p>This placeholder document is provided so local and staging pages can load. Replace it with approved legal content before production release.</p>';
        return [
            ['uuid' => $this->uuid('terms'), 'document_type' => 'terms', 'title' => 'Terms of Service', 'slug' => 'terms', 'version' => '0.1-review', 'content' => $placeholder, 'seo' => ['title' => 'Terms of Service | PerrymanFinance', 'description' => 'Terms placeholder for PerrymanFinance pending legal approval.', 'robots' => 'noindex,follow']],
            ['uuid' => $this->uuid('privacy-policy'), 'document_type' => 'privacy_policy', 'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'version' => '0.1-review', 'content' => $placeholder, 'seo' => ['title' => 'Privacy Policy | PerrymanFinance', 'description' => 'Privacy placeholder for PerrymanFinance pending legal approval.', 'robots' => 'noindex,follow']],
            ['uuid' => $this->uuid('risk-disclosure'), 'document_type' => 'risk_disclosure', 'title' => 'Risk Disclosure', 'slug' => 'risk-disclosure', 'version' => '0.1-review', 'content' => $placeholder, 'seo' => ['title' => 'Risk Disclosure | PerrymanFinance', 'description' => 'Risk disclosure placeholder for PerrymanFinance pending legal approval.', 'robots' => 'noindex,follow']],
            ['uuid' => $this->uuid('cookie-policy'), 'document_type' => 'cookie_policy', 'title' => 'Cookie Policy', 'slug' => 'cookie-policy', 'version' => '0.1-review', 'content' => $placeholder, 'seo' => ['title' => 'Cookie Policy | PerrymanFinance', 'description' => 'Cookie policy placeholder for PerrymanFinance pending legal approval.', 'robots' => 'noindex,follow']],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function pages(): array
    {
        return [
            $this->page('home', 'PerrymanFinance', 'Institutional digital asset and wealth-management information.', [
                $this->section('hero', 'hero', 'Considered digital asset wealth management', 'Explore institutional-style information about investment approach, risk discipline, and consultation routes.'),
                $this->section('positioning', 'rich_text', 'Built for informed decisions', 'PerrymanFinance presents research-led information for prospective clients evaluating digital asset and wealth-management services.'),
                $this->grid('services', 'service_grid', 'Investment services', [['Investment Solutions', 'Structured service information for research and consultation.', '/investment-solutions'], ['Digital Asset Management', 'Educational content on digital asset exposure and risk.', '/digital-assets'], ['Wealth Management', 'Longer-term planning and stewardship information.', '/wealth-management']]),
                $this->section('philosophy', 'rich_text', 'Risk-first investment philosophy', 'Public content focuses on objectives, constraints, governance, and risk. It does not offer transactions or promise outcomes.'),
                $this->section('opportunities', 'investment_preview', 'Featured opportunities', ''),
                $this->grid('process', 'process_steps', 'How it works', [['Learn', 'Review published service and risk information.', '/how-it-works'], ['Request information', 'Send a consultation enquiry for follow-up.', '/contact'], ['Discuss suitability', 'Continue through appropriate offline review steps.', '/risk-disclosure']]),
                $this->section('risk', 'rich_text', 'Risk information', 'Digital assets can be volatile and may not be suitable for every client. Review the risk disclosure before requesting information.'),
                $this->section('insights', 'insights_preview', 'Latest insights', ''),
                $this->section('cta', 'cta', 'Request information', 'Use the enquiry form to start a reviewed conversation with the team.'),
            ]),
            $this->page('about', 'About PerrymanFinance', 'Company positioning and investment philosophy.', [$this->section('overview', 'rich_text', 'About the firm', 'PerrymanFinance is presented as an informational digital asset and wealth-management website for education, publishing, and qualified enquiries.'), $this->section('philosophy', 'rich_text', 'Philosophy', 'The content model emphasizes transparent communication, risk awareness, and administrator-reviewed public information.')]),
            $this->page('investment-solutions', 'Investment Solutions', 'Informational overview of investment services.', [$this->section('overview', 'rich_text', 'Investment solutions', 'Explore service information designed for prospective clients. This website does not accept funds or execute investments.'), $this->section('opportunities', 'investment_preview', 'Published opportunities', '')]),
            $this->page('digital-assets', 'Digital Asset Management', 'Educational digital asset management overview.', [$this->section('overview', 'rich_text', 'Digital asset management', 'Content explains digital asset themes, governance considerations, and risk factors at a high level.'), $this->section('risk', 'rich_text', 'Risk awareness', 'Digital asset exposure involves volatility, technology, liquidity, operational, and regulatory risks.')]),
            $this->page('wealth-management', 'Wealth Management', 'Wealth strategy and planning information.', [$this->section('overview', 'rich_text', 'Wealth management', 'PerrymanFinance publishes information about long-term planning, diversification concepts, and consultation workflows.'), $this->section('cta', 'cta', 'Request information', 'Contact the team for a reviewed discussion.')]),
            $this->page('how-it-works', 'How It Works', 'Informational enquiry process.', [$this->grid('process', 'process_steps', 'Process', [['Review content', 'Read public service, insight, and risk information.', '/insights'], ['Submit enquiry', 'Share your question through the contact form.', '/contact'], ['Follow-up', 'The team can respond outside the website with appropriate next steps.', '/risk-disclosure']])]),
            $this->page('contact', 'Request Information', 'Submit an enquiry to PerrymanFinance.', [$this->section('overview', 'rich_text', 'Contact', 'Use the form to request information. Submitting an enquiry does not create an account, investment, wallet, deposit, or transaction.')]),
            $this->page('investments', 'Investment Opportunities', 'Published informational investment-opportunity catalogue.', [$this->section('overview', 'rich_text', 'Investment opportunities', 'Published opportunities are informational catalogue entries only. Review all risk information before enquiring.')]),
            $this->page('insights', 'Insights', 'Published PerrymanFinance insights and educational commentary.', [$this->section('overview', 'rich_text', 'Insights', 'Read administrator-reviewed educational commentary and company perspectives.')]),
            $this->page('faq', 'Frequently Asked Questions', 'Answers to common PerrymanFinance questions.', [$this->section('overview', 'faq_preview', 'Frequently asked questions', '')]),
        ];
    }

    /**
     * @param list<array{0:string,1:string,2:string}> $items
     * @return array{type:string,content:array<string,mixed>}
     */
    private function grid(string $slot, string $type, string $heading, array $items): array
    {
        return ['type' => $type, 'content' => ['slot' => $slot, 'heading' => $heading, 'items' => array_map(static fn (array $item): array => ['title' => $item[0], 'body' => $item[1], 'href' => $item[2]], $items)]];
    }

    /** @return array{type:string,content:array<string,mixed>} */
    private function section(string $slot, string $type, string $heading, string $body): array
    {
        return ['type' => $type, 'content' => ['slot' => $slot, 'heading' => $heading, 'body' => $body]];
    }

    /**
     * @param list<array{type:string,content:array<string,mixed>}> $sections
     * @return array<string,mixed>
     */
    private function page(string $slug, string $title, string $excerpt, array $sections): array
    {
        return [
            'uuid' => $this->uuid('page-' . $slug),
            'title' => $title,
            'slug' => $slug,
            'page_type' => 'marketing',
            'excerpt' => $excerpt,
            'sections' => $sections,
            'seo' => ['title' => $title . ' | PerrymanFinance', 'description' => $excerpt, 'robots' => 'index,follow'],
        ];
    }

    private function uuid(string $key): string
    {
        $hash = md5('perrymanfinance-' . $key);
        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-4' . substr($hash, 13, 3) . '-a' . substr($hash, 17, 3) . '-' . substr($hash, 20, 12);
    }
}
