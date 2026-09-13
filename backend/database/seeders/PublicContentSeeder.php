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
        $investmentCategories = [];
        foreach ($this->investmentCategories() as $category) {
            $investmentCategories[$category['slug']] = $this->upsertInvestmentCategory($connection, $category, $now);
        }
        foreach ($this->investmentOpportunities() as $opportunity) {
            $opportunityId = $this->upsertInvestmentOpportunity($connection, $opportunity, $investmentCategories, $actor, $now);
            $this->upsertSeo($connection, 'investment_opportunity_id', $opportunityId, $opportunity['seo'], $now);
        }
        $articleCategories = [];
        foreach ($this->articleCategories() as $category) {
            $articleCategories[$category['slug']] = $this->upsertArticleCategory($connection, $category, $now);
        }
        $tags = [];
        foreach ($this->tags() as $tag) {
            $tags[$tag['slug']] = $this->upsertTag($connection, $tag, $now);
        }
        foreach ($this->articles() as $article) {
            $articleId = $this->upsertArticle($connection, $article, $articleCategories, $tags, $actor, $now);
            $this->upsertSeo($connection, 'article_id', $articleId, $article['seo'], $now);
        }
        foreach ($this->faqs() as $faq) {
            $this->upsertFaq($connection, $faq, $actor, $now);
        }
        foreach ($this->settings() as $key => $value) {
            $statement = $connection->prepare(
                'INSERT INTO site_settings (setting_key,value_json,is_public,updated_by,created_at,updated_at)
                VALUES (:key,:value,1,:actor,:created_at,:updated_at)
                ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),is_public=VALUES(is_public),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)',
            );
            $statement->execute(['key' => $key, 'value' => json_encode($value, JSON_THROW_ON_ERROR), 'actor' => $actor, 'created_at' => $now, 'updated_at' => $now]);
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
            VALUES (:uuid,:email,:password,:name,:status,:created_at,:updated_at)',
        );
        $statement->execute([
            'uuid' => $this->uuid('seed-content-admin'),
            'email' => 'seed-content-admin@example.test',
            'password' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
            'name' => 'Content Seeder',
            'status' => 'inactive',
            'created_at' => $now,
            'updated_at' => $now,
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
            VALUES (:uuid,:title,:slug,:type,:status,:excerpt,:content,:published,:created_by,:updated_by,:created_at,:updated_at)
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
            'created_by' => $actor,
            'updated_by' => $actor,
            'created_at' => $now,
            'updated_at' => $now,
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
            VALUES (:page,:type,:position,:content,:created_at,:updated_at)',
        );
        foreach ($sections as $position => $section) {
            $insert->execute([
                'page' => $pageId,
                'type' => $section['type'],
                'position' => $position,
                'content' => json_encode($section['content'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
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
            VALUES (:uuid,:type,:title,:slug,:version,:content,:effective,:status,:published,:created_by,:updated_by,:created_at,:updated_at)
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
            'created_by' => $actor,
            'updated_by' => $actor,
            'created_at' => $now,
            'updated_at' => $now,
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
            VALUES (:owner,:title,:description,NULL,:robots,:open_graph,NULL,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),robots=VALUES(robots),open_graph_json=VALUES(open_graph_json),updated_at=VALUES(updated_at)",
        );
        $statement->execute([
            'owner' => $ownerId,
            'title' => $seo['title'],
            'description' => $seo['description'],
            'robots' => $seo['robots'],
            'open_graph' => json_encode($seo['open_graph'] ?? null, JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function idForSlug(PDO $connection, string $table, string $slug): int
    {
        $statement = $connection->prepare("SELECT id FROM {$table} WHERE slug=:slug ORDER BY id DESC LIMIT 1");
        $statement->execute(['slug' => $slug]);
        return (int) $statement->fetchColumn();
    }

    /** @param array<string,mixed> $category */
    private function upsertInvestmentCategory(PDO $connection, array $category, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO investment_categories (name,slug,description,position,created_at,updated_at)
            VALUES (:name,:slug,:description,:position,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),position=VALUES(position),updated_at=VALUES(updated_at)',
        );
        $statement->execute([...$category, 'created_at' => $now, 'updated_at' => $now]);
        return $this->idForSlug($connection, 'investment_categories', (string) $category['slug']);
    }

    /**
     * @param array<string,mixed> $opportunity
     * @param array<string,int> $categories
     */
    private function upsertInvestmentOpportunity(PDO $connection, array $opportunity, array $categories, int $actor, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO investment_opportunities (uuid,category_id,title,slug,short_description,full_description,strategy_summary,investment_objective,investment_horizon,risk_classification,minimum_investment_display,currency_display,status,featured,cover_media_id,disclaimer,published_at,created_by,updated_by,created_at,updated_at)
            VALUES (:uuid,:category,:title,:slug,:short,:full,:strategy,:objective,:horizon,:risk,:minimum,:currency,:status,:featured,NULL,:disclaimer,:published,:created_by,:updated_by,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE category_id=VALUES(category_id),title=VALUES(title),short_description=VALUES(short_description),full_description=VALUES(full_description),strategy_summary=VALUES(strategy_summary),investment_objective=VALUES(investment_objective),investment_horizon=VALUES(investment_horizon),risk_classification=VALUES(risk_classification),minimum_investment_display=VALUES(minimum_investment_display),currency_display=VALUES(currency_display),status=VALUES(status),featured=VALUES(featured),disclaimer=VALUES(disclaimer),published_at=VALUES(published_at),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at),deleted_at=NULL',
        );
        $statement->execute([
            'uuid' => $opportunity['uuid'],
            'category' => $categories[$opportunity['category']],
            'title' => $opportunity['title'],
            'slug' => $opportunity['slug'],
            'short' => $opportunity['short'],
            'full' => $opportunity['full'],
            'strategy' => $opportunity['strategy'],
            'objective' => $opportunity['objective'],
            'horizon' => $opportunity['horizon'],
            'risk' => $opportunity['risk'],
            'minimum' => $opportunity['minimum'],
            'currency' => $opportunity['currency'],
            'status' => 'published',
            'featured' => $opportunity['featured'] ? 1 : 0,
            'disclaimer' => $opportunity['disclaimer'],
            'published' => $now,
            'created_by' => $actor,
            'updated_by' => $actor,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->idForSlug($connection, 'investment_opportunities', (string) $opportunity['slug']);
    }

    /** @param array<string,mixed> $category */
    private function upsertArticleCategory(PDO $connection, array $category, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO article_categories (name,slug,description,created_at,updated_at)
            VALUES (:name,:slug,:description,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),updated_at=VALUES(updated_at)',
        );
        $statement->execute([...$category, 'created_at' => $now, 'updated_at' => $now]);
        return $this->idForSlug($connection, 'article_categories', (string) $category['slug']);
    }

    /** @param array<string,string> $tag */
    private function upsertTag(PDO $connection, array $tag, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO tags (name,slug,created_at,updated_at)
            VALUES (:name,:slug,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE name=VALUES(name),updated_at=VALUES(updated_at)',
        );
        $statement->execute([...$tag, 'created_at' => $now, 'updated_at' => $now]);
        return $this->idForSlug($connection, 'tags', $tag['slug']);
    }

    /**
     * @param array<string,mixed> $article
     * @param array<string,int> $categories
     * @param array<string,int> $tags
     */
    private function upsertArticle(PDO $connection, array $article, array $categories, array $tags, int $actor, string $now): int
    {
        $statement = $connection->prepare(
            'INSERT INTO articles (uuid,category_id,title,slug,excerpt,content,cover_media_id,author_id,status,featured,published_at,created_at,updated_at)
            VALUES (:uuid,:category,:title,:slug,:excerpt,:content,NULL,:author,:status,:featured,:published,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE category_id=VALUES(category_id),title=VALUES(title),excerpt=VALUES(excerpt),content=VALUES(content),author_id=VALUES(author_id),status=VALUES(status),featured=VALUES(featured),published_at=VALUES(published_at),updated_at=VALUES(updated_at),deleted_at=NULL',
        );
        $statement->execute([
            'uuid' => $article['uuid'],
            'category' => $categories[$article['category']],
            'title' => $article['title'],
            'slug' => $article['slug'],
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
            'author' => $actor,
            'status' => 'published',
            'featured' => $article['featured'] ? 1 : 0,
            'published' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $articleId = $this->idForSlug($connection, 'articles', (string) $article['slug']);
        $connection->prepare('DELETE FROM article_tags WHERE article_id=:article')->execute(['article' => $articleId]);
        $insert = $connection->prepare('INSERT INTO article_tags (article_id,tag_id,created_at) VALUES (:article,:tag,:created_at)');
        foreach ($article['tags'] as $slug) {
            $insert->execute(['article' => $articleId, 'tag' => $tags[$slug], 'created_at' => $now]);
        }
        return $articleId;
    }

    /** @param array<string,mixed> $faq */
    private function upsertFaq(PDO $connection, array $faq, int $actor, string $now): void
    {
        $existing = $connection->prepare('SELECT id FROM faqs WHERE question=:question LIMIT 1');
        $existing->execute(['question' => $faq['question']]);
        $id = $existing->fetchColumn();
        if ($id === false) {
            $statement = $connection->prepare(
                'INSERT INTO faqs (question,answer,category,position,status,published_at,created_by,updated_by,created_at,updated_at)
                VALUES (:question,:answer,:category,:position,:status,:published,:created_by,:updated_by,:created_at,:updated_at)',
            );
            $statement->execute([...$faq, 'status' => 'published', 'published' => $now, 'created_by' => $actor, 'updated_by' => $actor, 'created_at' => $now, 'updated_at' => $now]);
            return;
        }
        $statement = $connection->prepare(
            'UPDATE faqs SET answer=:answer,category=:category,position=:position,status=:status,published_at=:published,updated_by=:updated_by,updated_at=:updated_at WHERE id=:id',
        );
        $statement->execute(['id' => $id, 'answer' => $faq['answer'], 'category' => $faq['category'], 'position' => $faq['position'], 'status' => 'published', 'published' => $now, 'updated_by' => $actor, 'updated_at' => $now]);
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return [
            'risk_statement' => '<p>PerrymanFinance publishes financial-services, securities, digital asset, portfolio-management, and wealth-management content for approved public review and client engagement. Capital is at risk, values can move materially, and public content is not a guarantee, personalized recommendation, or claim of unverified licensing, custody, exchange, or trading-execution capability.</p>',
            'contact_details' => '<p>Use the enquiry form to request consultation, onboarding intake, client account support, product information, or market-insight follow-up from the PerrymanFinance team. A submission does not by itself create an account, subscription, transaction, wallet, deposit, withdrawal, custody arrangement, or advisory relationship.</p>',
            'enquiry_consent' => 'I consent to PerrymanFinance using my submitted details to respond to this enquiry, consultation, onboarding-intake, or client-service request and understand that any regulated account, investment, transaction, custody, or reporting service requires approved separate documentation and controls.',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function legalDocuments(): array
    {
        $review = '<p><strong>Review status:</strong> This CMS-managed document is a structured release placeholder. It is not production legal advice or a complete agreement. The business and qualified counsel must replace or approve it before the relevant public service, form, or release is enabled.</p>';
        $terms = $review
            . '<h2>Purpose and scope</h2><p>This page is intended to set out the terms governing use of the PerrymanFinance public website and any approved client-facing services. The final version must identify the actual operating entity, jurisdiction, services, eligibility requirements, fees where applicable, and contact route for formal notices.</p>'
            . '<h2>Public information and decisions</h2><p>Website content is provided to support general education and an informed conversation. It is not a personal recommendation, an offer to transact, a guarantee of outcome, or a substitute for suitability, legal, tax, accounting, or other professional advice where required. Visitors remain responsible for decisions made using public information.</p>'
            . '<h2>Service boundaries</h2><p>The current public platform does not create wallets, hold private keys, accept deposits or withdrawals, process payments, execute trades, create investment subscriptions, maintain client-money accounts, or calculate investment returns. Any future regulated or transactional capability requires separate approved terms, controls, and implementation.</p>'
            . '<h2>Website use and content</h2><p>The approved final terms should describe permitted use, intellectual-property rights, acceptable-use restrictions, availability, third-party content, disclaimers, limitation of liability, indemnities, governing law, dispute resolution, changes to the terms, and how users will be notified of material changes.</p>'
            . '<h2>Before publication</h2><p>Confirm the legal entity, jurisdiction, regulatory status, service descriptions, fee disclosures, client eligibility, complaint process, governing law, dispute mechanism, notice details, and effective-date/change-notice process with the business and qualified counsel.</p>';
        $privacy = $review
            . '<h2>Purpose and data practices</h2><p>This page is intended to explain how PerrymanFinance handles personal information collected through approved public interactions, including contact, consultation, and onboarding-intake enquiries. The final policy must match the actual systems, recipients, hosting locations, retention controls, and legal obligations in use.</p>'
            . '<h2>Information submitted through enquiries</h2><p>The public enquiry workflow is designed to collect only the information needed to respond: name, email address, optional phone number, enquiry type, subject, message, source page, and consent record. Visitors should not send account credentials, private keys, payment instructions, or unnecessary sensitive information through public forms.</p>'
            . '<h2>Purpose, access, and security</h2><p>Approved enquiry information is used to receive, route, and respond to the request, subject to the final approved policy. Access must be restricted to authorized personnel. The final policy must describe the actual security measures, service providers, international transfers if any, retention periods, deletion/export procedures, and incident-response contacts.</p>'
            . '<h2>Your choices and rights</h2><p>The final policy must explain applicable rights to access, correct, delete, restrict, object to, or export personal information, how consent can be withdrawn where relevant, the identity of the data controller, and how to raise a privacy question or complaint.</p>'
            . '<h2>Before publication</h2><p>Confirm the controller identity and contact details, lawful bases, data categories, purposes, recipients, retention schedule, cross-border transfers, security controls, individual-rights process, regulator/complaint route, and any jurisdiction-specific notices with privacy counsel and the business.</p>';
        $risk = $review
            . '<h2>Capital and market risk</h2><p>All investments involve risk and may lose value. Market prices can move materially because of economic conditions, interest rates, inflation, currencies, corporate events, investor sentiment, and other factors. Past performance, where lawfully shown and properly contextualized, is not a reliable indicator of future results.</p>'
            . '<h2>Liquidity, concentration, and time horizon</h2><p>Some assets or opportunities may be difficult to value, sell, transfer, or exit at a desired time or price. Concentrated positions can amplify losses. An investment decision should be considered against objectives, existing exposures, liquidity needs, liabilities, time horizon, and capacity to absorb loss.</p>'
            . '<h2>Digital asset and technology risk</h2><p>Digital assets can be highly volatile and may involve market-structure, liquidity, technology, cybersecurity, protocol, counterparty, legal, tax, and regulatory risks. The public platform does not provide wallet creation, private-key handling, custody, asset transfer, payment processing, or trading execution.</p>'
            . '<h2>Third-party, legal, and operational risk</h2><p>Outcomes may be affected by counterparties, service providers, market infrastructure, systems availability, fraud, cyber incidents, documentation, tax treatment, and changing laws or regulations. These factors may alter the availability, value, or suitability of an investment or service.</p>'
            . '<h2>Decisions and professional advice</h2><p>Risk classifications and public disclosures are starting points, not a complete assessment of suitability. Review relevant documents carefully and seek appropriate independent legal, tax, accounting, and financial advice before making a decision. A consultation request does not create an account, move funds or assets, or commit either party to a transaction.</p>'
            . '<h2>Before publication</h2><p>Qualified counsel and the business must confirm that this disclosure reflects approved products, target jurisdictions, actual services, regulatory requirements, required warnings, and the correct relationship to any formal client documentation.</p>';
        $cookies = $review
            . '<h2>Purpose of this policy</h2><p>This page is intended to explain the cookies and similar technologies used on the PerrymanFinance website, why they are used, and how visitors can manage their choices. The final version must reflect an approved cookie inventory and the consent mechanism actually deployed.</p>'
            . '<h2>Categories to confirm</h2><p>The final policy should distinguish strictly necessary technologies from preference, analytics, performance, advertising, and third-party technologies where used. It must state the provider, purpose, duration, and whether each technology is first-party or third-party.</p>'
            . '<h2>Your choices</h2><p>Where consent is required, non-essential technologies must not be placed before a visitor makes the relevant choice. The final implementation must provide an accessible way to accept, reject, or adjust non-essential categories and explain browser-level controls and the limits of those controls.</p>'
            . '<h2>Related information</h2><p>Cookies and similar technologies can involve personal information. The final Cookie Policy should link to the approved Privacy Policy and identify how visitors can ask questions about privacy choices.</p>'
            . '<h2>Before publication</h2><p>Confirm the live cookie scan, technology inventory, vendor details, purposes, durations, legal basis, geographic targeting, consent records, preference-centre behavior, and update process with the business, privacy counsel, and implementation team.</p>';
        return [
            ['uuid' => $this->uuid('terms'), 'document_type' => 'terms', 'title' => 'Terms of Service', 'slug' => 'terms', 'version' => '0.2-review', 'content' => $terms, 'seo' => ['title' => 'Terms of Service | PerrymanFinance', 'description' => 'CMS-managed PerrymanFinance terms review document pending business and legal approval.', 'robots' => 'noindex,follow']],
            ['uuid' => $this->uuid('privacy-policy'), 'document_type' => 'privacy_policy', 'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'version' => '0.2-review', 'content' => $privacy, 'seo' => ['title' => 'Privacy Policy | PerrymanFinance', 'description' => 'CMS-managed PerrymanFinance privacy review document pending business and legal approval.', 'robots' => 'noindex,follow']],
            ['uuid' => $this->uuid('risk-disclosure'), 'document_type' => 'risk_disclosure', 'title' => 'Risk Disclosure', 'slug' => 'risk-disclosure', 'version' => '0.2-review', 'content' => $risk, 'seo' => ['title' => 'Risk Disclosure | PerrymanFinance', 'description' => 'Risk information for PerrymanFinance public investment and digital asset content.', 'robots' => 'noindex,follow']],
            ['uuid' => $this->uuid('cookie-policy'), 'document_type' => 'cookie_policy', 'title' => 'Cookie Policy', 'slug' => 'cookie-policy', 'version' => '0.2-review', 'content' => $cookies, 'seo' => ['title' => 'Cookie Policy | PerrymanFinance', 'description' => 'CMS-managed PerrymanFinance cookie review document pending business and legal approval.', 'robots' => 'noindex,follow']],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function pages(): array
    {
        return [
            $this->page('home', 'PerrymanFinance', 'Investment solutions, wealth management, securities-market perspective, digital asset research, and client-service support.', [
                $this->section('hero', 'hero', 'Clearer investment decisions begin with disciplined advice', '<p>PerrymanFinance brings investment solutions, wealth and portfolio management, securities-market perspective, digital asset research, and client-service support into one considered financial-services experience. Explore the services, review opportunity and risk information, and request a consultation when you are ready to discuss your priorities.</p>'),
                $this->section('positioning', 'rich_text', 'A considered approach to modern wealth', '<p>PerrymanFinance supports private investors, families, founders, and professional allocators who need a clearer way to assess financial markets and investment themes. Our public platform explains how investment solutions, wealth planning, portfolio context, digital assets, and risk management can inform a more deliberate conversation.</p><p>Each route is designed to help clients understand the questions, information, and review process that may be relevant before engaging with a service or opportunity.</p>'),
                $this->grid('services', 'service_grid', 'Financial services built around your objectives', [['Investment Solutions', 'Explore investment themes and published opportunities through objectives, time horizon, liquidity needs, risk classification, and their potential role within a broader portfolio.', '/investment-solutions'], ['Digital Asset Management', 'Assess digital asset exposure with attention to market structure, volatility, operational resilience, governance, counterparties, and the place of emerging assets in a wider allocation.', '/digital-assets'], ['Wealth Management', 'Frame long-term wealth priorities around diversification, liquidity, family or business interests, portfolio review, client reporting needs, and informed decision-making.', '/wealth-management']]),
                $this->section('philosophy', 'rich_text', 'Investment decisions deserve more than a market view', '<p>Our approach starts with the client context: objectives, existing exposures, liquidity needs, time horizon, governance, and tolerance for loss. We then consider the market, operational, and documentation questions that can affect an investment decision.</p><p>Published opportunities are intended to support informed review. They describe a theme, its risk classification, and the information needed for a meaningful discussion; they are not a promise of outcome or a substitute for suitability assessment.</p>'),
                $this->section('opportunities', 'investment_preview', 'Explore investment opportunities with context', '<p>Review published opportunities with their stated objective, time horizon, risk classification, and disclosure information before deciding whether to request a discussion.</p>'),
                $this->section('risk', 'rich_text', 'Risk management belongs at the start of the conversation', '<p>Every investment involves risk, including the possible loss of capital. Securities and digital assets can be affected by market volatility, liquidity constraints, economic conditions, counterparty exposure, technology failures, cyber incidents, tax considerations, and regulatory change.</p><p>Risk classification and disclosure are starting points, not a complete assessment. Review the relevant information and seek appropriate professional advice before making a financial decision.</p>'),
                $this->section('cta', 'cta', 'Talk to PerrymanFinance about your priorities', '<p>Request a consultation about investment solutions, wealth-management priorities, a digital asset strategy, client-service needs, or a published opportunity. We will use your enquiry to direct you to the appropriate next conversation.</p>'),
            ]),
            $this->page('about', 'About PerrymanFinance', 'Investment solutions, wealth-management context, securities-market perspective, and digital asset research for informed client conversations.', [
                $this->section('overview', 'rich_text', 'Financial services for considered decisions', '<p>PerrymanFinance is a financial services platform focused on investment solutions, wealth-management context, securities-market perspective, and digital asset research. We help prospective and existing clients understand the information, questions, and risks that can shape an investment or wealth-planning conversation.</p><p>Our website brings together service information, published investment opportunities, market insights, and risk disclosures in one place. It is designed to make the first stage of client engagement more useful: clear about the subject under review, the decisions it may inform, and the matters that require further discussion.</p>'),
                $this->grid('services', 'feature_grid', 'Our service focus', [
                    ['Investment solutions', 'Review published investment themes and opportunities through their objectives, time horizon, liquidity considerations, risk classification, and possible role within a broader portfolio.', '/investment-solutions'],
                    ['Wealth management', 'Frame longer-term financial priorities around diversification, liquidity, family or business interests, portfolio context, and the information needed for a well-prepared discussion.', '/wealth-management'],
                    ['Digital asset management', 'Examine digital asset exposure with attention to market structure, volatility, operational resilience, governance, counterparties, and its relationship to wider financial objectives.', '/digital-assets'],
                ]),
                $this->section('approach', 'rich_text', 'How we work with clients', '<p>We begin with context rather than a product. Objectives, existing exposures, liquidity requirements, time horizon, governance, and tolerance for loss help determine which questions matter most. From there, market conditions, risk information, operational considerations, and supporting documentation can be reviewed in a more relevant way.</p><p>For prospective clients, the public platform is a starting point for education and enquiry. A consultation request allows the appropriate next conversation to be identified without implying that a website visit creates an account, an investment, or a transaction.</p>'),
                $this->grid('values', 'feature_grid', 'What clients can expect from PerrymanFinance', [
                    ['Clear communication', 'We explain services, opportunity themes, and relevant risks in direct language so clients can understand what they are reviewing and what needs further consideration.', '/faq'],
                    ['Risk-aware perspective', 'Investment and market information is presented with risk classification, disclosures, and practical context rather than promises of performance or pressure to act.', '/risk-disclosure'],
                    ['Structured follow-through', 'Consultation and onboarding enquiries provide a clear route to the right next discussion, whether that concerns a service, a published opportunity, or client support.', '/how-it-works'],
                ]),
                $this->section('mission', 'rich_text', 'Our mission and business objective', '<p>Our mission is to support more informed financial conversations by connecting accessible market education with disciplined client engagement. PerrymanFinance is built for people who want to understand investment themes, wealth-management priorities, and digital asset considerations before deciding whether to seek a detailed consultation.</p><p>We communicate opportunity and risk together. Published content supports review and discussion; it does not promise outcomes, provide automatic investment processing, or replace a suitability, legal, tax, or professional advice process where one is required.</p>'),
                $this->section('why-perrymanfinance', 'rich_text', 'A more useful starting point for financial decisions', '<p>Choosing PerrymanFinance means starting with information that is organised around the realities of financial decision-making: objectives, constraints, market context, risk, and the questions behind a potential allocation. The platform combines those elements across investment solutions, wealth management, securities-market context, and digital assets instead of treating them as isolated topics.</p><p>Clients can explore the available information at their own pace, review relevant disclosures, and request a consultation when they need a focused conversation. This approach supports informed engagement without relying on unsupported claims, fabricated performance, or artificial urgency.</p>'),
                $this->section('cta', 'cta', 'Request a consultation', '<p>Talk with PerrymanFinance about an investment theme, wealth-management priority, digital asset question, published opportunity, or client-service need. Your enquiry helps direct you to the appropriate next conversation.</p>'),
            ]),
            $this->page('investment-solutions', 'Investment Solutions', 'Investment services, product review, and portfolio implementation overview.', [$this->section('overview', 'rich_text', 'Investment solutions', '<p>Explore financial-services workflows for prospective and existing clients comparing investment products, securities exposure, digital asset strategy, portfolio objectives, liquidity needs, and account-service requirements. Public pages do not by themselves accept funds or execute investments.</p>'), $this->grid('focus', 'feature_grid', 'What the product workflow helps clarify', [['Objective', 'Each entry describes the general purpose of the opportunity and the type of discussion it supports.', '/investments'], ['Risk classification', 'Entries are labelled with risk classifications so visitors can begin from suitability and tolerance, not headline narratives.', '/risk-disclosure'], ['Information request', 'Visitors can request consultation or onboarding intake without automatically creating an account, wallet, subscription, or transaction.', '/contact']]), $this->section('opportunities', 'investment_preview', 'Published opportunities', '')]),
            $this->page('digital-assets', 'Digital Asset Management', 'A research-led approach to digital asset exposure, market structure, operational resilience, and risk-aware portfolio context.', [
                ['type' => 'service_experience', 'content' => [
                    'slot' => 'service-experience', 'variant' => 'digital-assets', 'eyebrow' => 'Digital Asset Management',
                    'heading' => 'Digital asset exposure deserves a wider lens.',
                    'body' => '<p>Digital assets can introduce new sources of opportunity and risk into a portfolio conversation. PerrymanFinance helps prospective and existing clients explore the market, operational, governance, liquidity, and risk questions that may matter before deciding whether to request a detailed discussion.</p><p>Public content is educational and risk-aware. It does not provide trading execution, wallet creation, private-key handling, custody, payment, or asset-transfer services.</p>',
                    'items' => [
                        ['title' => 'Market structure and liquidity', 'body' => '<p>Digital asset markets can operate continuously, fragment liquidity across venues, and reprice rapidly. Understanding market access, liquidity conditions, and price formation helps frame a more realistic discussion.</p>', 'href' => '/insights'],
                        ['title' => 'Portfolio role and concentration', 'body' => '<p>An exposure should be considered alongside wider assets, income needs, liabilities, existing concentrations, time horizon, and capacity for loss rather than as an isolated market view.</p>', 'href' => '/wealth-management'],
                        ['title' => 'Operational resilience', 'body' => '<p>Governance, permissions, counterparties, cybersecurity, reconciliation, and incident response can materially affect an operating model. These questions require careful review where relevant.</p>', 'href' => '/risk-disclosure'],
                        ['title' => 'Risk and regulatory context', 'body' => '<p>Volatility, technology change, legal and regulatory uncertainty, tax complexity, and third-party dependency can affect digital asset decisions and require appropriate specialist input.</p>', 'href' => '/risk-disclosure'],
                    ],
                    'principles' => [
                        ['title' => 'Research before reaction', 'body' => '<p>Market commentary and published information should help identify questions, not create pressure to act on short-term price movement.</p>', 'href' => ''],
                        ['title' => 'Controls matter', 'body' => '<p>A strategy discussion can include the operating assumptions and third-party dependencies that influence implementation and oversight.</p>', 'href' => ''],
                        ['title' => 'Risk stays visible', 'body' => '<p>Potential loss, liquidity constraints, operational issues, and changing regulation should remain part of the discussion from the beginning.</p>', 'href' => ''],
                    ],
                    'questions' => [
                        ['title' => 'What role could this exposure serve?', 'body' => '<p>Consider the objective, time horizon, liquidity needs, and how any exposure would interact with the rest of a financial picture.</p>', 'href' => ''],
                        ['title' => 'What risks would need to be understood?', 'body' => '<p>Review market, technology, liquidity, counterparty, operational, tax, and regulatory considerations before moving forward.</p>', 'href' => ''],
                        ['title' => 'What information would make a conversation useful?', 'body' => '<p>Bring a clear question, relevant constraints, and only the information needed to describe the topic. Do not send private keys, credentials, or payment instructions.</p>', 'href' => ''],
                    ],
                ]],
                $this->section('cta', 'cta', 'Discuss a digital asset question with context', '<p>Request a consultation about market structure, portfolio context, published opportunities, operational considerations, or the risk information you would like to understand more clearly.</p>'),
            ]),
            $this->page('wealth-management', 'Wealth Management', 'A considered wealth-management conversation built around objectives, liquidity, diversification, governance, and long-term decision-making.', [
                ['type' => 'service_experience', 'content' => [
                    'slot' => 'service-experience', 'variant' => 'wealth-management', 'eyebrow' => 'Wealth Management',
                    'heading' => 'A financial plan should reflect the whole picture.',
                    'body' => '<p>Wealth management is more than selecting an investment theme. PerrymanFinance helps frame conversations around objectives, liquidity, existing assets and liabilities, family or business interests, governance, time horizon, and the risks that can affect long-term decisions.</p><p>Our public platform helps you prepare for a useful consultation. It does not provide personalised advice, open an account, accept funds, or create an investment or transaction.</p>',
                    'items' => [
                        ['title' => 'Objectives and time horizon', 'body' => '<p>Clarify what a financial decision is intended to support, when resources may be needed, and the trade-offs that may arise between near-term flexibility and longer-term goals.</p>', 'href' => '/how-it-works'],
                        ['title' => 'Liquidity and resilience', 'body' => '<p>Review foreseeable obligations, reserve needs, income requirements, and the risks of allocating capital to assets that may be volatile or difficult to exit.</p>', 'href' => '/risk-disclosure'],
                        ['title' => 'Diversification and concentration', 'body' => '<p>Consider how public markets, private interests, business ownership, real assets, and emerging asset classes may interact within a broader financial picture.</p>', 'href' => '/investment-solutions'],
                        ['title' => 'Governance and communication', 'body' => '<p>Documented objectives, clear decision-making roles, and regular communication can help families, founders, and stakeholders assess complex choices with more consistency.</p>', 'href' => '/contact?type=consultation'],
                    ],
                    'principles' => [
                        ['title' => 'Context before product', 'body' => '<p>The client situation, not a headline or product category, should determine the questions considered first.</p>', 'href' => ''],
                        ['title' => 'Long-term discipline', 'body' => '<p>Time horizon, liquidity, and the ability to tolerate uncertainty can matter as much as an investment idea itself.</p>', 'href' => ''],
                        ['title' => 'Clear review points', 'body' => '<p>Consistent communication and documented priorities can make it easier to revisit decisions as circumstances or markets change.</p>', 'href' => ''],
                    ],
                    'questions' => [
                        ['title' => 'What is the decision meant to support?', 'body' => '<p>Describe the financial objective, the relevant timeframe, and the outcome you want the discussion to help clarify.</p>', 'href' => ''],
                        ['title' => 'What must remain flexible?', 'body' => '<p>Think through liquidity needs, commitments, income requirements, existing exposures, and factors that would make an allocation unsuitable.</p>', 'href' => ''],
                        ['title' => 'Who should be part of the conversation?', 'body' => '<p>Where family, business, legal, tax, or other stakeholders are relevant, note the decision-making and communication context before requesting a consultation.</p>', 'href' => ''],
                    ],
                ]],
                $this->section('cta', 'cta', 'Talk through your wealth-management priorities', '<p>Request a consultation about long-term financial priorities, portfolio context, liquidity questions, family or business interests, or a published opportunity.</p>'),
            ]),
            $this->page('how-it-works', 'How It Works', 'A clear, risk-aware path from exploring a service to requesting the appropriate next conversation.', [
                ['type' => 'client_journey', 'content' => [
                    'slot' => 'journey',
                    'eyebrow' => 'Your client journey',
                    'heading' => 'Start with context. Move forward with clarity.',
                    'body' => '<p>PerrymanFinance is designed to help prospective and existing clients understand a service, published opportunity, market theme, or support question before deciding whether to request a conversation. The path is structured, but it is never a one-size-fits-all transaction flow.</p><p>Explore the relevant information, consider the risks, and share the question you would like to discuss. We use that context to direct the enquiry to the appropriate next step.</p>',
                    'items' => [
                        ['title' => 'Explore the relevant information', 'body' => '<p>Review our services, published opportunities, insights, FAQs, and risk information. Start with the topic that best reflects your question, whether that is wealth planning, an investment theme, digital assets, or client support.</p>', 'href' => '/investment-solutions'],
                        ['title' => 'Frame your objectives and constraints', 'body' => '<p>Consider what you are trying to achieve, your time horizon, liquidity needs, existing exposures, decision-makers, and your ability to bear loss. These questions provide context; they are not a substitute for any formal assessment that may later be required.</p>', 'href' => '/wealth-management'],
                        ['title' => 'Review the relevant risks', 'body' => '<p>Read the risk classification, disclosures, and any product-specific information. Market conditions, volatility, liquidity, operational dependencies, regulation, tax, and third parties can all affect an outcome.</p>', 'href' => '/risk-disclosure'],
                        ['title' => 'Request a consultation or support conversation', 'body' => '<p>Use the enquiry form to describe your question and choose the most relevant reason for contacting us. A team member can route the request toward the appropriate service, opportunity review, onboarding-intake discussion, or support path.</p>', 'href' => '/contact?type=consultation'],
                        ['title' => 'Discuss the appropriate next steps', 'body' => '<p>The next conversation may cover the information needed, relevant service scope, documentation, timing, risk considerations, and any approved requirements that apply. It does not imply an investment, account, transaction, or transfer of assets.</p>', 'href' => '/faq'],
                        ['title' => 'Proceed only through approved processes', 'body' => '<p>Where formal onboarding, suitability, contractual, compliance, custody, reporting, or transaction processes apply, they require the relevant approved controls and documentation. Public website content cannot replace those processes.</p>', 'href' => '/terms'],
                    ],
                    'checkpoints' => [
                        ['title' => 'Is the objective clear?', 'body' => '<p>Be specific about the decision or question you need help exploring. A clear objective makes it easier to identify the information and people relevant to the conversation.</p>', 'href' => '/faq'],
                        ['title' => 'Is the risk understood?', 'body' => '<p>Investment values can fall as well as rise. A risk classification is a starting point, not a complete personal assessment or a promise of outcome.</p>', 'href' => '/risk-disclosure'],
                        ['title' => 'Is the information complete enough to discuss?', 'body' => '<p>Bring only what is relevant to your question and avoid sending unnecessary sensitive information through public channels.</p>', 'href' => '/privacy-policy'],
                    ],
                    'preparation' => [
                        ['title' => 'Your question', 'body' => '<p>Tell us whether you are exploring a service, a published opportunity, a market topic, or a client-support matter.</p>', 'href' => ''],
                        ['title' => 'Your priorities', 'body' => '<p>It may help to note your time horizon, liquidity considerations, and the key question you want the discussion to answer.</p>', 'href' => ''],
                        ['title' => 'Your preferred contact details', 'body' => '<p>Provide the details needed for a response and confirm that you consent to being contacted about your enquiry.</p>', 'href' => ''],
                    ],
                ]],
                $this->section('cta', 'cta', 'Ready to discuss your priorities?', '<p>Request a consultation about a service, investment theme, wealth-management priority, digital asset question, published opportunity, or client-support need. Your enquiry helps direct you to the appropriate next conversation.</p>'),
            ]),
            $this->page('contact', 'Client Services', 'Request consultation, onboarding intake, or client service from PerrymanFinance.', [$this->section('overview', 'rich_text', 'Contact', 'Use the form to request consultation, onboarding intake, account-service follow-up, product information, or market-insight support. Submitting an enquiry does not by itself create an account, investment, wallet, deposit, withdrawal, custody arrangement, or transaction.')]),
            $this->page('investments', 'Investment Opportunities', 'Published investment products and opportunity catalogue.', [$this->section('overview', 'rich_text', 'Investment opportunities', 'Published opportunities are controlled product records with objectives, horizons, risk classifications, and disclaimers. Review all risk information before requesting consultation or onboarding intake.')]),
            $this->page('insights', 'Insights', 'Published PerrymanFinance market and investment insights.', [$this->section('overview', 'rich_text', 'Insights', 'Read administrator-reviewed market commentary, investment research, risk notes, portfolio context, and company perspectives.')]),
            $this->page('faq', 'Frequently Asked Questions', 'Answers to common PerrymanFinance questions.', [$this->section('overview', 'faq_preview', 'Frequently asked questions', '')]),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function investmentCategories(): array
    {
        return [
            ['name' => 'Digital Asset Exposure', 'slug' => 'digital-asset-exposure', 'description' => 'Investment opportunities focused on diversified digital asset market exposure and portfolio implementation review.', 'position' => 10],
            ['name' => 'Income and Liquidity', 'slug' => 'income-and-liquidity', 'description' => 'Catalogue entries for discussing liquidity-aware yield and treasury-style themes without guaranteed returns.', 'position' => 20],
            ['name' => 'Wealth Strategy', 'slug' => 'wealth-strategy', 'description' => 'Longer-horizon planning themes that connect digital assets with broader wealth objectives.', 'position' => 30],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function investmentOpportunities(): array
    {
        $disclaimer = '<p>This product and opportunity entry is for controlled review, consultation, and onboarding-intake discussion. It is not a guarantee, fabricated performance claim, wallet, custody service, exchange service, or automatic transaction facility. Capital is at risk.</p>';
        return [
            ['uuid' => $this->uuid('opportunity-core-digital-asset-strategy'), 'category' => 'digital-asset-exposure', 'title' => 'Core Digital Asset Strategy', 'slug' => 'core-digital-asset-strategy', 'short' => 'A diversified digital asset exposure discussion for clients seeking a measured entry point into the asset class.', 'full' => '<p>This strategy describes a diversified approach to digital asset exposure, with emphasis on governance, liquidity, custody considerations, and rebalancing discipline.</p><p>It is designed for consultation conversations where the client wants to understand how major digital asset themes could be reviewed within a broader portfolio context.</p>', 'strategy' => '<p>Focus on diversified exposure, risk classification, allocation discipline, and periodic review rather than short-term trading.</p>', 'objective' => '<p>Support an informed conversation about whether digital asset exposure has an appropriate role in a client&apos;s broader objectives and constraints.</p>', 'horizon' => 'Medium to long term', 'risk' => 'high', 'minimum' => 'Discuss during consultation', 'currency' => 'USD', 'featured' => true, 'disclaimer' => $disclaimer, 'seo' => ['title' => 'Core Digital Asset Strategy | PerrymanFinance', 'description' => 'Digital asset exposure product entry for consultation, onboarding intake, and risk review.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('opportunity-digital-treasury-reserve'), 'category' => 'income-and-liquidity', 'title' => 'Digital Treasury Reserve Review', 'slug' => 'digital-treasury-reserve-review', 'short' => 'A liquidity and treasury-management discussion for clients evaluating digital asset market access and reserve planning.', 'full' => '<p>This entry supports a conversation about reserve objectives, liquidity windows, counterparty review, operational controls, and the limits of cash-equivalent language in digital asset markets.</p><p>No yield, return, or preservation outcome is promised by this catalogue entry.</p>', 'strategy' => '<p>Review liquidity needs, eligible instruments, third-party dependencies, operational risks, and reporting expectations.</p>', 'objective' => '<p>Help prospective clients evaluate whether a digital-asset-adjacent reserve discussion is appropriate for their wider cash and liquidity planning.</p>', 'horizon' => 'Short to medium term', 'risk' => 'moderate', 'minimum' => 'Request details', 'currency' => 'USD', 'featured' => true, 'disclaimer' => $disclaimer, 'seo' => ['title' => 'Digital Treasury Reserve Review | PerrymanFinance', 'description' => 'Liquidity and treasury planning product entry for digital asset contexts.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('opportunity-long-horizon-wealth-planning'), 'category' => 'wealth-strategy', 'title' => 'Long-Horizon Wealth Planning Review', 'slug' => 'long-horizon-wealth-planning-review', 'short' => 'A planning-led discussion for families, founders, and long-term investors considering digital assets alongside traditional wealth needs.', 'full' => '<p>This entry frames digital asset exposure within broader wealth planning: liquidity, concentration, estate context, tax questions, reporting needs, and stakeholder communication.</p><p>It does not replace regulated tax, legal, or personalized investment advice.</p>', 'strategy' => '<p>Start with objectives, liabilities, income needs, concentration risk, and family or business governance before discussing any asset-specific exposure.</p>', 'objective' => '<p>Prepare a structured discussion about how emerging asset classes may or may not fit a long-term wealth plan.</p>', 'horizon' => 'Long term', 'risk' => 'high', 'minimum' => 'Discuss during consultation', 'currency' => 'USD', 'featured' => true, 'disclaimer' => $disclaimer, 'seo' => ['title' => 'Long-Horizon Wealth Planning Review | PerrymanFinance', 'description' => 'Wealth planning product entry for digital asset portfolio discussions.', 'robots' => 'index,follow']],
        ];
    }

    /** @return list<array<string,string>> */
    private function articleCategories(): array
    {
        return [
            ['name' => 'Research Notes', 'slug' => 'research-notes', 'description' => 'Market structure, securities, and portfolio research notes.'],
            ['name' => 'Risk Education', 'slug' => 'risk-education', 'description' => 'Plain-language risk, governance, and operational resilience content.'],
            ['name' => 'Wealth Planning', 'slug' => 'wealth-planning', 'description' => 'Long-term planning and client conversation themes.'],
        ];
    }

    /** @return list<array<string,string>> */
    private function tags(): array
    {
        return [
            ['name' => 'Digital Assets', 'slug' => 'digital-assets'],
            ['name' => 'Risk', 'slug' => 'risk'],
            ['name' => 'Portfolio Construction', 'slug' => 'portfolio-construction'],
            ['name' => 'Governance', 'slug' => 'governance'],
            ['name' => 'Liquidity', 'slug' => 'liquidity'],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function articles(): array
    {
        return [
            ['uuid' => $this->uuid('article-digital-assets-in-a-wealth-context'), 'category' => 'wealth-planning', 'title' => 'Digital Assets in a Wealth-Management Context', 'slug' => 'digital-assets-in-a-wealth-management-context', 'excerpt' => 'A planning-led overview of how digital asset exposure can be discussed alongside objectives, liquidity, and risk tolerance.', 'content' => '<p>Digital assets should not be evaluated in isolation. A useful conversation starts with objectives, time horizon, liquidity needs, existing concentration, operational complexity, and risk tolerance.</p><p>For some clients, the appropriate outcome may be education only. For others, the next step may be a deeper suitability and governance review outside this website.</p>', 'featured' => true, 'tags' => ['digital-assets', 'portfolio-construction', 'risk'], 'seo' => ['title' => 'Digital Assets in a Wealth-Management Context | PerrymanFinance', 'description' => 'Professional overview of digital asset exposure within broader wealth planning.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('article-risk-before-return'), 'category' => 'risk-education', 'title' => 'Why Risk Language Should Come Before Return Expectations', 'slug' => 'risk-language-before-return-expectations', 'excerpt' => 'Clear risk framing helps investors avoid treating volatile asset classes as simple return stories.', 'content' => '<p>Return expectations are incomplete without risk language. Digital asset markets can reprice quickly, liquidity may change under stress, and operational dependencies can matter as much as market direction.</p><p>A disciplined review describes what can go wrong, how exposure is monitored, and when a position or theme should be revisited.</p>', 'featured' => true, 'tags' => ['risk', 'governance'], 'seo' => ['title' => 'Risk Language Before Return Expectations | PerrymanFinance', 'description' => 'Educational note on risk-first communication for digital asset investing.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('article-liquidity-questions'), 'category' => 'research-notes', 'title' => 'Liquidity Questions for Digital Asset Allocations', 'slug' => 'liquidity-questions-for-digital-asset-allocations', 'excerpt' => 'Questions prospective clients can ask when reviewing liquidity, access, and exit assumptions.', 'content' => '<p>Liquidity is not only a market-depth question. It also includes venue access, settlement process, withdrawal constraints, counterparty dependencies, documentation, and reporting cadence.</p><p>Before any allocation discussion, clients should understand what information is needed to evaluate access and what assumptions may fail under stress.</p>', 'featured' => true, 'tags' => ['liquidity', 'digital-assets', 'risk'], 'seo' => ['title' => 'Liquidity Questions for Digital Asset Allocations | PerrymanFinance', 'description' => 'Educational liquidity checklist for digital asset allocation discussions.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('article-governance-checklist'), 'category' => 'risk-education', 'title' => 'A Governance Checklist for Emerging-Asset Conversations', 'slug' => 'governance-checklist-for-emerging-asset-conversations', 'excerpt' => 'A concise governance checklist for families, founders, and investment committees reviewing emerging asset exposure.', 'content' => '<p>Governance helps turn interest in an emerging asset class into a documented decision process. Useful questions include who can approve exposure, what risk limits apply, how information is reported, and when decisions should be reviewed.</p><p>This article should be adapted to the client&apos;s legal, tax, and advisory context.</p>', 'featured' => false, 'tags' => ['governance', 'portfolio-construction'], 'seo' => ['title' => 'Governance Checklist for Emerging Assets | PerrymanFinance', 'description' => 'Educational governance checklist for emerging asset conversations.', 'robots' => 'index,follow']],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function faqs(): array
    {
        return [
            ['question' => 'What does PerrymanFinance do?', 'answer' => '<p>PerrymanFinance presents investment solutions, wealth-management context, securities-market perspective, digital asset research, published opportunities, and client-service information. The platform is designed to help prospective and existing clients understand relevant questions before requesting a conversation.</p>', 'category' => 'general', 'position' => 10],
            ['question' => 'Who can use the public platform?', 'answer' => '<p>The public platform is for people exploring PerrymanFinance services, reviewing educational market content, considering a published opportunity, or seeking client support. Information on the site is intended to support informed discussion, not replace professional advice tailored to your circumstances.</p>', 'category' => 'general', 'position' => 20],
            ['question' => 'Which services can I explore?', 'answer' => '<p>You can explore investment solutions, wealth-management priorities, digital asset considerations, investment opportunities, risk information, and market insights. Each service page explains the subject matter and directs you to the appropriate next conversation.</p>', 'category' => 'services', 'position' => 30],
            ['question' => 'How do I request a consultation or client-service follow-up?', 'answer' => '<p>Use the contact form to submit a general enquiry, consultation request, investment-product question, or client-service request. Provide only the information needed for the team to understand and route your request.</p>', 'category' => 'getting-started', 'position' => 40],
            ['question' => 'Does submitting an enquiry create an account or investment?', 'answer' => '<p>No. An enquiry is a request for follow-up. It does not by itself create an account, establish a client relationship, accept funds, or place an investment instruction.</p>', 'category' => 'getting-started', 'position' => 50],
            ['question' => 'Are published investment opportunities offers or personal recommendations?', 'answer' => '<p>No. Published opportunities are catalogue entries provided for review and discussion. They are not a personal recommendation, a guarantee of outcome, or an instruction to proceed with an investment.</p>', 'category' => 'investment-opportunities', 'position' => 60],
            ['question' => 'What does a risk classification mean?', 'answer' => '<p>A risk classification provides a starting point for considering volatility, liquidity, market conditions, operational factors, and potential loss. It is not a complete suitability assessment and should be read with the relevant disclosure information.</p>', 'category' => 'investment-opportunities', 'position' => 70],
            ['question' => 'Are investment returns guaranteed?', 'answer' => '<p>No. Investment values can rise or fall, and loss of capital is possible. Past market behaviour does not reliably indicate future results.</p>', 'category' => 'investment-opportunities', 'position' => 80],
            ['question' => 'How does PerrymanFinance approach digital asset discussions?', 'answer' => '<p>Digital asset discussions consider market structure, volatility, liquidity, governance, operational resilience, counterparties, and the possible role of an exposure within wider financial objectives. Digital assets involve material risks and may not be suitable for every client or objective.</p>', 'category' => 'services', 'position' => 90],
            ['question' => 'Does PerrymanFinance provide wallet, custody, funding, or withdrawal services through this website?', 'answer' => '<p>No. The public website does not provide wallet creation, private-key handling, custody, deposits, withdrawals, payments, or trading execution.</p>', 'category' => 'platform-boundaries', 'position' => 100],
            ['question' => 'How is my enquiry information used?', 'answer' => '<p>Information submitted through the contact form is used to receive and route your enquiry. Please review the Privacy Policy for the published terms that apply and avoid sending unnecessary sensitive information through the form.</p>', 'category' => 'privacy-and-security', 'position' => 110],
            ['question' => 'Where can I find support or ask a question not covered here?', 'answer' => '<p>Use the contact form or the support contact details on the Contact page. The team can direct your question to the appropriate service, opportunity, or client-support conversation.</p>', 'category' => 'customer-support', 'position' => 120],
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
