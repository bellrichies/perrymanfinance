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
            'risk_statement' => '<p>Digital asset and wealth-management information is provided for education and enquiry only. Capital is at risk, values can move materially, and no content on this website is a recommendation, offer, custody service, wallet, exchange, or trading facility.</p>',
            'contact_details' => '<p>Use the enquiry form to request information from the PerrymanFinance team. A submission does not create an account, subscription, transaction, wallet, deposit, withdrawal, or advisory relationship.</p>',
            'enquiry_consent' => 'I consent to PerrymanFinance using my submitted details to respond to this enquiry and understand this website does not process investments or transactions.',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function legalDocuments(): array
    {
        $placeholder = '<p>This review placeholder is provided so local and staging pages can load with realistic structure. Replace it with approved legal content before production release.</p><h2>Purpose of this page</h2><p>The final document must be prepared or reviewed by qualified counsel and reflect the real operating entity, jurisdiction, regulatory position, services, fees, data practices, dispute process, and client obligations.</p><h2>Current status</h2><p>This seeded version is not production legal copy and must remain under review until approved by the business and counsel.</p>';
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
                $this->section('hero', 'hero', 'Investment services for modern wealth decisions', '<p>PerrymanFinance presents investment services, wealth-management information, digital asset research, and structured opportunity reviews for clients who want a disciplined way to evaluate financial markets. Explore the firm&apos;s service areas, review risk information, and request a consultation when you are ready for a direct conversation.</p>'),
                $this->section('positioning', 'rich_text', 'A financial services partner for informed clients', '<p>PerrymanFinance helps private investors, families, founders, and professional allocators review investment themes with clearer context. The company&apos;s public platform explains service capabilities across investment solutions, wealth and portfolio management, digital asset exposure, client account discussions, market insight, and risk management.</p><p>The website is informational and enquiry-led. It does not accept deposits, create wallets, custody assets, execute trades, process investment subscriptions, or provide automated portfolio accounting.</p>'),
                $this->grid('services', 'service_grid', 'Services designed around advice, access, and discipline', [['Investment Solutions', 'Structured information for clients comparing objectives, time horizon, liquidity needs, risk classification, and the role an opportunity may play in a broader portfolio.', '/investment-solutions'], ['Digital Asset Management', 'Research-led education on digital asset exposure, market structure, custody considerations, operational controls, and governance requirements.', '/digital-assets'], ['Wealth Management', 'Planning-focused content for diversification, liquidity management, family and business-owner priorities, reporting needs, and long-term financial decision-making.', '/wealth-management']]),
                $this->section('philosophy', 'rich_text', 'Built around judgement, governance, and restraint', '<p>The PerrymanFinance approach begins with objectives, constraints, suitability, liquidity, documentation, and risk. Investment opportunities are presented as catalogue entries for review and discussion, not as offers, promises, or pressure-based calls to act.</p><p>Public content connects market insight with portfolio context so visitors can understand what a service is, what risks may affect it, and which questions should be addressed before any financial decision is made.</p>'),
                $this->section('opportunities', 'investment_preview', 'Featured investment opportunities', '<p>Review published opportunity themes prepared for informational discussion. Each entry includes a stated objective, horizon, risk classification, and disclaimer so enquiries begin with a balanced view of potential considerations.</p>'),
                $this->grid('process', 'process_steps', 'How the service conversation works', [['Explore services', 'Review the firm&apos;s investment, wealth-management, digital asset, and market-insight information.', '/investment-solutions'], ['Review risks', 'Read the relevant risk information and consider whether the topic fits your objectives, liquidity needs, and tolerance for loss.', '/risk-disclosure'], ['Request consultation', 'Submit an enquiry so the team can respond with the appropriate information path outside the website.', '/contact'], ['Proceed through review', 'Any account, suitability, documentation, or onboarding process occurs only through approved offline procedures.', '/how-it-works']]),
                $this->grid('credibility', 'feature_grid', 'Why clients consider PerrymanFinance', [['Integrated market perspective', 'Traditional financial markets and digital asset themes are discussed together, helping clients compare exposure, diversification, and macro drivers.', '/insights'], ['Risk-first communication', 'Service and opportunity content avoids guaranteed returns, fabricated performance, and artificial urgency while keeping risk disclosure visible.', '/risk-disclosure'], ['Controlled client enquiry path', 'Public calls to action lead to information requests and consultation, preserving clear boundaries around accounts, transactions, custody, and execution.', '/faq']]),
                $this->section('risk', 'rich_text', 'Risk management is part of every discussion', '<p>Investments can lose value, and digital assets may experience significant volatility, liquidity constraints, technology failures, cyber incidents, regulatory change, tax complexity, and third-party risk. PerrymanFinance frames opportunities through risk classification, time horizon, operational considerations, and portfolio fit before any next step is discussed.</p><p>Visitors should review the risk disclosure and seek appropriate professional advice before making financial decisions.</p>'),
                $this->section('insights', 'insights_preview', 'Financial insights for better decisions', '<p>Read educational commentary on global markets, digital assets, liquidity, portfolio construction, governance, and risk. Insights are intended to support informed questions, not to provide individualized recommendations.</p>'),
                $this->section('cta', 'cta', 'Request information from PerrymanFinance', '<p>Start with a focused enquiry about investment services, wealth-management priorities, digital asset strategy, or a published opportunity. Submitting the form does not create an account, investment, wallet, deposit, withdrawal, trade, or advisory relationship.</p>'),
            ]),
            $this->page('about', 'About PerrymanFinance', 'Professional market access, disciplined research, and risk-aware wealth conversations.', [
                $this->section('overview', 'rich_text', 'A modern finance partner for global investors', '<p>PerrymanFinance helps individuals, families, founders, and institutions approach global markets with clearer information, stronger discipline, and a structured path for enquiry. The public website presents our market perspective, service areas, investment-opportunity catalogue, insights, and risk disclosures so prospective clients can prepare for informed conversations.</p><p>Our focus is simple: make sophisticated financial themes easier to understand without reducing them to slogans, hype, or unsupported promises. We communicate opportunity and risk together, and we keep every public interaction grounded in education, governance, and responsible follow-up.</p>'),
                $this->grid('values', 'feature_grid', 'What defines the PerrymanFinance approach', [
                    ['Research-led perspective', 'We frame market themes through objectives, liquidity, risk classification, macro context, and portfolio relevance rather than short-term noise.', '/insights'],
                    ['Governance before action', 'Every conversation should begin with constraints, suitability, documentation, decision rights, and the controls needed for responsible participation.', '/how-it-works'],
                    ['Global market awareness', 'We help visitors understand how equities, currencies, digital assets, commodities, rates, and economic news can affect allocation decisions.', '/investment-solutions'],
                ]),
                $this->section('mission', 'rich_text', 'Our mission', '<p>Our mission is to give clients a clearer route into modern markets by combining accessible education with professional standards of communication. PerrymanFinance is designed for people who want to ask better questions before they allocate capital, compare opportunity themes, or request a more detailed discussion.</p><p>The website does not execute trades, accept deposits, create wallets, provide custody, or process investments. It is a public information and enquiry platform that supports a more deliberate offline review process.</p>'),
                $this->grid('principles', 'feature_grid', 'Operating principles', [
                    ['Clarity', 'Financial information should explain what a service is, what it is not, and which risks require further discussion.', '/risk-disclosure'],
                    ['Discipline', 'Investment conversations should begin with objectives, constraints, liquidity, time horizon, diversification, and governance.', '/wealth-management'],
                    ['Restraint', 'We avoid countdowns, guaranteed-return language, fabricated performance, fake balances, and pressure-based financial messaging.', '/faq'],
                ]),
                $this->section('philosophy', 'rich_text', 'Built for informed decisions, not impulsive transactions', '<p>PerrymanFinance presents market and wealth-management information for review before any direct conversation begins. We believe durable financial decisions come from understanding the role an opportunity may play, the risks that can change the outcome, and the operational requirements behind the strategy.</p><p>That philosophy shapes the structure of the site: published opportunity entries include risk classification and disclaimers, insights are educational, and public calls to action lead to enquiry rather than instant execution.</p>'),
                $this->grid('audience', 'feature_grid', 'Who the platform is designed for', [
                    ['Private investors', 'Investors who want a clearer view of digital assets and global market themes before requesting follow-up information.', '/investments'],
                    ['Families and founders', 'Decision-makers balancing liquidity, concentration, succession, tax questions, and long-term wealth objectives.', '/wealth-management'],
                    ['Professional allocators', 'Teams that need concise, risk-aware material for initial review and structured internal discussion.', '/contact'],
                ]),
                $this->section('cta', 'cta', 'Trade, Scale, and Command Your Financial Future', '<p>Start with a focused enquiry and let the PerrymanFinance team respond with the appropriate information path.</p>'),
            ]),
            $this->page('investment-solutions', 'Investment Solutions', 'Informational overview of investment services.', [$this->section('overview', 'rich_text', 'Investment solutions', '<p>Explore service information designed for prospective clients comparing structured digital asset and wealth-management themes. This website does not accept funds or execute investments.</p>'), $this->grid('focus', 'feature_grid', 'What the catalogue helps clarify', [['Objective', 'Each entry describes the general purpose of the opportunity and the type of discussion it supports.', '/investments'], ['Risk classification', 'Entries are labelled with risk classifications so visitors can begin from suitability and tolerance, not headline narratives.', '/risk-disclosure'], ['Information request', 'Visitors can request more information without creating an account, wallet, subscription, or transaction.', '/contact']]), $this->section('opportunities', 'investment_preview', 'Published opportunities', '')]),
            $this->page('digital-assets', 'Digital Asset Management', 'Educational digital asset management overview.', [$this->section('overview', 'rich_text', 'Digital asset management', '<p>Digital asset content explains themes such as market access, custody considerations, liquidity, governance, security controls, and regulatory uncertainty at a high level. The aim is to help visitors prepare better questions before a suitability conversation.</p>'), $this->grid('considerations', 'feature_grid', 'Key considerations', [['Market structure', 'Digital asset markets operate continuously and may experience fragmented liquidity, rapid repricing, and venue-specific risks.', '/insights'], ['Operational controls', 'Exposure should be discussed alongside custody model, counterparty review, permissions, reconciliation, and incident response.', '/risk-disclosure'], ['Portfolio role', 'Digital asset exposure should be considered in the context of wider assets, liquidity needs, income profile, and time horizon.', '/wealth-management']]), $this->section('risk', 'rich_text', 'Risk awareness', '<p>Digital asset exposure involves volatility, technology, liquidity, operational, tax, legal, and regulatory risks. Past market behavior should not be treated as a reliable guide to future results.</p>')]),
            $this->page('wealth-management', 'Wealth Management', 'Wealth strategy and planning information.', [$this->section('overview', 'rich_text', 'Wealth management', '<p>PerrymanFinance publishes information about long-term planning, diversification concepts, liquidity management, family and business-owner context, and consultation workflows. The seeded content is educational and must be refined with approved business copy before production.</p>'), $this->grid('planning', 'feature_grid', 'Planning themes', [['Liquidity and horizon', 'Clarify near-term obligations, reserve needs, and time horizon before discussing illiquid or volatile exposure.', '/how-it-works'], ['Diversification', 'Review how digital asset themes may interact with traditional assets, business interests, income needs, and risk tolerance.', '/investment-solutions'], ['Communication', 'Use documented objectives and risk language so stakeholders can review decisions consistently over time.', '/contact']]), $this->section('cta', 'cta', 'Request information', '<p>Contact the team for a reviewed discussion.</p>')]),
            $this->page('how-it-works', 'How It Works', 'Informational enquiry process.', [$this->section('overview', 'rich_text', 'A clear enquiry path', '<p>The MVP supports education and lead capture only. The process below describes how a prospective client can move from public information to a direct follow-up conversation without transacting on the website.</p>'), $this->grid('process', 'process_steps', 'Process', [['Review content', 'Read public service, insight, opportunity, and risk information.', '/insights'], ['Submit enquiry', 'Share your question and contact details through the public form.', '/contact'], ['Follow-up', 'The team can respond outside the website with appropriate information requests and next steps.', '/risk-disclosure'], ['Document decisions', 'Any production onboarding, suitability, contractual, or compliance process must happen outside this MVP until formally scoped.', '/terms']])]),
            $this->page('contact', 'Request Information', 'Submit an enquiry to PerrymanFinance.', [$this->section('overview', 'rich_text', 'Contact', 'Use the form to request information. Submitting an enquiry does not create an account, investment, wallet, deposit, or transaction.')]),
            $this->page('investments', 'Investment Opportunities', 'Published informational investment-opportunity catalogue.', [$this->section('overview', 'rich_text', 'Investment opportunities', 'Published opportunities are informational catalogue entries only. Review all risk information before enquiring.')]),
            $this->page('insights', 'Insights', 'Published PerrymanFinance insights and educational commentary.', [$this->section('overview', 'rich_text', 'Insights', 'Read administrator-reviewed educational commentary and company perspectives.')]),
            $this->page('faq', 'Frequently Asked Questions', 'Answers to common PerrymanFinance questions.', [$this->section('overview', 'faq_preview', 'Frequently asked questions', '')]),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function investmentCategories(): array
    {
        return [
            ['name' => 'Digital Asset Exposure', 'slug' => 'digital-asset-exposure', 'description' => 'Informational opportunities focused on diversified digital asset market exposure.', 'position' => 10],
            ['name' => 'Income and Liquidity', 'slug' => 'income-and-liquidity', 'description' => 'Catalogue entries for discussing liquidity-aware yield and treasury-style themes without guaranteed returns.', 'position' => 20],
            ['name' => 'Wealth Strategy', 'slug' => 'wealth-strategy', 'description' => 'Longer-horizon planning themes that connect digital assets with broader wealth objectives.', 'position' => 30],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function investmentOpportunities(): array
    {
        $disclaimer = '<p>This catalogue entry is for information and enquiry only. It is not an offer, recommendation, guarantee, wallet, custody service, exchange service, or transaction facility. Capital is at risk.</p>';
        return [
            ['uuid' => $this->uuid('opportunity-core-digital-asset-strategy'), 'category' => 'digital-asset-exposure', 'title' => 'Core Digital Asset Strategy', 'slug' => 'core-digital-asset-strategy', 'short' => 'A diversified digital asset exposure discussion for clients seeking a measured entry point into the asset class.', 'full' => '<p>This informational strategy describes a diversified approach to digital asset exposure, with emphasis on governance, liquidity, custody considerations, and rebalancing discipline.</p><p>It is designed for consultation conversations where the client wants to understand how major digital asset themes could be reviewed within a broader portfolio context.</p>', 'strategy' => '<p>Focus on diversified exposure, risk classification, allocation discipline, and periodic review rather than short-term trading.</p>', 'objective' => '<p>Support an informed conversation about whether digital asset exposure has an appropriate role in a client&apos;s broader objectives and constraints.</p>', 'horizon' => 'Medium to long term', 'risk' => 'high', 'minimum' => 'Discuss during consultation', 'currency' => 'USD', 'featured' => true, 'disclaimer' => $disclaimer, 'seo' => ['title' => 'Core Digital Asset Strategy | PerrymanFinance', 'description' => 'Informational digital asset exposure catalogue entry for consultation and risk review.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('opportunity-digital-treasury-reserve'), 'category' => 'income-and-liquidity', 'title' => 'Digital Treasury Reserve Review', 'slug' => 'digital-treasury-reserve-review', 'short' => 'A liquidity and treasury-management discussion for clients evaluating digital asset market access and reserve planning.', 'full' => '<p>This entry supports a conversation about reserve objectives, liquidity windows, counterparty review, operational controls, and the limits of cash-equivalent language in digital asset markets.</p><p>No yield, return, or preservation outcome is promised by this catalogue entry.</p>', 'strategy' => '<p>Review liquidity needs, eligible instruments, third-party dependencies, operational risks, and reporting expectations.</p>', 'objective' => '<p>Help prospective clients evaluate whether a digital-asset-adjacent reserve discussion is appropriate for their wider cash and liquidity planning.</p>', 'horizon' => 'Short to medium term', 'risk' => 'moderate', 'minimum' => 'Request details', 'currency' => 'USD', 'featured' => true, 'disclaimer' => $disclaimer, 'seo' => ['title' => 'Digital Treasury Reserve Review | PerrymanFinance', 'description' => 'Informational liquidity and treasury planning catalogue entry for digital asset contexts.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('opportunity-long-horizon-wealth-planning'), 'category' => 'wealth-strategy', 'title' => 'Long-Horizon Wealth Planning Review', 'slug' => 'long-horizon-wealth-planning-review', 'short' => 'A planning-led discussion for families, founders, and long-term investors considering digital assets alongside traditional wealth needs.', 'full' => '<p>This entry frames digital asset exposure within broader wealth planning: liquidity, concentration, estate context, tax questions, reporting needs, and stakeholder communication.</p><p>It is informational only and does not replace regulated tax, legal, or investment advice.</p>', 'strategy' => '<p>Start with objectives, liabilities, income needs, concentration risk, and family or business governance before discussing any asset-specific exposure.</p>', 'objective' => '<p>Prepare a structured discussion about how emerging asset classes may or may not fit a long-term wealth plan.</p>', 'horizon' => 'Long term', 'risk' => 'high', 'minimum' => 'Discuss during consultation', 'currency' => 'USD', 'featured' => true, 'disclaimer' => $disclaimer, 'seo' => ['title' => 'Long-Horizon Wealth Planning Review | PerrymanFinance', 'description' => 'Informational wealth planning catalogue entry for digital asset portfolio discussions.', 'robots' => 'index,follow']],
        ];
    }

    /** @return list<array<string,string>> */
    private function articleCategories(): array
    {
        return [
            ['name' => 'Research Notes', 'slug' => 'research-notes', 'description' => 'Educational market structure and portfolio research notes.'],
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
            ['uuid' => $this->uuid('article-digital-assets-in-a-wealth-context'), 'category' => 'wealth-planning', 'title' => 'Digital Assets in a Wealth-Management Context', 'slug' => 'digital-assets-in-a-wealth-management-context', 'excerpt' => 'A planning-led overview of how digital asset exposure can be discussed alongside objectives, liquidity, and risk tolerance.', 'content' => '<p>Digital assets should not be evaluated in isolation. A useful conversation starts with objectives, time horizon, liquidity needs, existing concentration, operational complexity, and risk tolerance.</p><p>For some clients, the appropriate outcome may be education only. For others, the next step may be a deeper suitability and governance review outside this website.</p>', 'featured' => true, 'tags' => ['digital-assets', 'portfolio-construction', 'risk'], 'seo' => ['title' => 'Digital Assets in a Wealth-Management Context | PerrymanFinance', 'description' => 'Educational overview of digital asset exposure within broader wealth planning.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('article-risk-before-return'), 'category' => 'risk-education', 'title' => 'Why Risk Language Should Come Before Return Expectations', 'slug' => 'risk-language-before-return-expectations', 'excerpt' => 'Clear risk framing helps investors avoid treating volatile asset classes as simple return stories.', 'content' => '<p>Return expectations are incomplete without risk language. Digital asset markets can reprice quickly, liquidity may change under stress, and operational dependencies can matter as much as market direction.</p><p>A disciplined review describes what can go wrong, how exposure is monitored, and when a position or theme should be revisited.</p>', 'featured' => true, 'tags' => ['risk', 'governance'], 'seo' => ['title' => 'Risk Language Before Return Expectations | PerrymanFinance', 'description' => 'Educational note on risk-first communication for digital asset investing.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('article-liquidity-questions'), 'category' => 'research-notes', 'title' => 'Liquidity Questions for Digital Asset Allocations', 'slug' => 'liquidity-questions-for-digital-asset-allocations', 'excerpt' => 'Questions prospective clients can ask when reviewing liquidity, access, and exit assumptions.', 'content' => '<p>Liquidity is not only a market-depth question. It also includes venue access, settlement process, withdrawal constraints, counterparty dependencies, documentation, and reporting cadence.</p><p>Before any allocation discussion, clients should understand what information is needed to evaluate access and what assumptions may fail under stress.</p>', 'featured' => true, 'tags' => ['liquidity', 'digital-assets', 'risk'], 'seo' => ['title' => 'Liquidity Questions for Digital Asset Allocations | PerrymanFinance', 'description' => 'Educational liquidity checklist for digital asset allocation discussions.', 'robots' => 'index,follow']],
            ['uuid' => $this->uuid('article-governance-checklist'), 'category' => 'risk-education', 'title' => 'A Governance Checklist for Emerging-Asset Conversations', 'slug' => 'governance-checklist-for-emerging-asset-conversations', 'excerpt' => 'A concise governance checklist for families, founders, and investment committees reviewing emerging asset exposure.', 'content' => '<p>Governance helps turn interest in an emerging asset class into a documented decision process. Useful questions include who can approve exposure, what risk limits apply, how information is reported, and when decisions should be reviewed.</p><p>This article is educational and should be adapted to the client&apos;s legal, tax, and advisory context.</p>', 'featured' => false, 'tags' => ['governance', 'portfolio-construction'], 'seo' => ['title' => 'Governance Checklist for Emerging Assets | PerrymanFinance', 'description' => 'Educational governance checklist for emerging asset conversations.', 'robots' => 'index,follow']],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function faqs(): array
    {
        return [
            ['question' => 'Can I invest directly through this website?', 'answer' => '<p>No. The MVP is an informational website and enquiry platform only. It does not process investments, deposits, withdrawals, trades, wallets, custody, or subscriptions.</p>', 'category' => 'platform', 'position' => 10],
            ['question' => 'Are the investment opportunities offers or recommendations?', 'answer' => '<p>No. Published opportunities are catalogue entries for information and discussion. They are not offers, recommendations, guarantees, or personalized advice.</p>', 'category' => 'investments', 'position' => 20],
            ['question' => 'Why do opportunities show risk classifications?', 'answer' => '<p>Risk classifications help visitors start from suitability, volatility, liquidity, and operational considerations before requesting more information.</p>', 'category' => 'risk', 'position' => 30],
            ['question' => 'Does PerrymanFinance provide wallet or custody services?', 'answer' => '<p>No wallet creation, private-key handling, custody, deposits, withdrawals, or blockchain signing is part of this MVP.</p>', 'category' => 'platform', 'position' => 40],
            ['question' => 'What happens after I submit an enquiry?', 'answer' => '<p>The enquiry is stored for follow-up. Submission does not create an account, client relationship, investment instruction, or transaction.</p>', 'category' => 'contact', 'position' => 50],
            ['question' => 'Is the legal content final?', 'answer' => '<p>The seeded legal pages are review placeholders for development and staging. Production legal content must be approved by qualified counsel and the business.</p>', 'category' => 'legal', 'position' => 60],
            ['question' => 'Are returns guaranteed?', 'answer' => '<p>No. The website must not promise or imply guaranteed returns. Digital asset and investment values can fall as well as rise, and capital is at risk.</p>', 'category' => 'risk', 'position' => 70],
            ['question' => 'Can administrators edit this content?', 'answer' => '<p>Yes. Public pages, opportunities, insights, FAQs, legal content, SEO metadata, and settings are intended to be managed through the CMS.</p>', 'category' => 'cms', 'position' => 80],
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
