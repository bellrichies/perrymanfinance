<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\Exceptions\ForbiddenException;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Http\Middleware\PermissionMiddleware;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\ClientAuditRepository;
use PerrymanFinance\Repositories\ClientPlanRepository;
use PerrymanFinance\Repositories\ClientTokenRepository;
use PerrymanFinance\Repositories\ClientUserRepository;
use PerrymanFinance\Repositories\RateLimitRepository;
use PerrymanFinance\Services\ClientAccount\ClientIdentityService;
use PerrymanFinance\Services\ClientAccount\ClientPortalService;
use PerrymanFinance\Services\Identity\RateLimiter;
use PerrymanFinance\Services\Identity\TokenService;
use PHPUnit\Framework\TestCase;
use Tests\Support\CapturingClientNotifier;
use Tests\Support\SqliteConnection;

final class ClientAccountTest extends TestCase
{
    private PDO $pdo;
    private ClientIdentityService $identity;
    private ClientPortalService $portal;
    private CapturingClientNotifier $notifier;
    private ClientUserRepository $users;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->schema();
        $config = new Config(['app' => ['frontend_url' => 'https://example.test'], 'auth' => ['jwt_secret' => str_repeat('c', 48), 'refresh_ttl' => 3600, 'login_limit' => 5]]);
        $tokens = new TokenService($config);
        $this->users = new ClientUserRepository($connection);
        $this->notifier = new CapturingClientNotifier();
        $audit = new ClientAuditRepository($connection);
        $transaction = new TransactionManager($connection);
        $this->identity = new ClientIdentityService($this->users, new ClientTokenRepository($connection), $audit, $tokens, new RateLimiter(new RateLimitRepository($connection)), $transaction, $this->notifier, $config);
        $this->portal = new ClientPortalService(new ClientPlanRepository($connection), $this->users, $audit, $transaction);
        $this->seed();
    }

    public function testRegistrationVerificationLoginRefreshAndReset(): void
    {
        $this->registerClient('client@example.test');
        self::assertNotNull($this->notifier->verificationUrl);
        $client = $this->users->findByEmail('client@example.test');
        self::assertNotNull($client);
        $this->expectException(UnauthorizedException::class);
        $this->identity->login('client@example.test', 'Secure-client-123', '127.0.0.1', null);
    }

    public function testVerifiedClientCanSubmitRiskAcknowledgedPlanRequestAndSeeOwnStatus(): void
    {
        $client = $this->verifiedClient('client@example.test');
        $this->expectException(ValidationException::class);
        try {
            $this->portal->submitPlanRequest($client, ['investment_opportunity_uuid' => '22222222-2222-4222-8222-222222222222'], '127.0.0.1', null);
        } finally {
            $result = $this->portal->submitPlanRequest($client, ['investment_opportunity_uuid' => '22222222-2222-4222-8222-222222222222', 'risk_acknowledged' => true, 'requested_amount' => '1000', 'currency' => 'USD'], '127.0.0.1', 'req-1');
            self::assertSame('pending', $result['status']);
            $dashboard = $this->portal->dashboard($client);
            self::assertCount(1, $dashboard['plan_requests']);
            self::assertSame('pending', $dashboard['plan_requests'][0]['status']);
            self::assertStringContainsString('not wallets', $dashboard['disclaimer']);
        }
    }

    public function testAdminApprovalRequiresExplicitPermissionAndReason(): void
    {
        $client = $this->verifiedClient('client@example.test');
        $request = $this->portal->submitPlanRequest($client, ['investment_opportunity_uuid' => '22222222-2222-4222-8222-222222222222', 'risk_acknowledged' => true, 'currency' => 'USD'], '127.0.0.1', null);
        $admin = new AdminUser(1, 'admin-uuid', 'admin@example.test', 'hash', 'Admin', 'active', ['viewer'], ['clients.view']);
        $http = new Request('POST', '/admin/client-plan-requests/' . $request['uuid'] . '/approve');
        $http->setAttribute('admin_user', $admin);
        $this->expectException(ForbiddenException::class);
        (new PermissionMiddleware('client_plans.review'))->process($http, static fn (): Response => new Response([]));
    }

    public function testAdminReviewCreatesApprovedAccountAndAudit(): void
    {
        $client = $this->verifiedClient('client@example.test');
        $request = $this->portal->submitPlanRequest($client, ['investment_opportunity_uuid' => '22222222-2222-4222-8222-222222222222', 'risk_acknowledged' => true, 'requested_amount' => '2500', 'currency' => 'USD'], '127.0.0.1', null);
        $admin = new AdminUser(1, 'admin-uuid', 'admin@example.test', 'hash', 'Admin', 'active', ['ops'], ['client_plans.review', 'clients.view']);
        $review = $this->portal->review($request['uuid'], 'approved', 'Reviewed suitability conversation notes.', $admin, '127.0.0.1', 'req-2');
        self::assertSame('approved', $review['status']);
        $accounts = $this->pdo->query('SELECT COUNT(*) FROM client_investment_accounts');
        $audits = $this->pdo->query("SELECT COUNT(*) FROM client_audit_events WHERE action='admin.client_plan_request_approved'");
        self::assertInstanceOf(\PDOStatement::class, $accounts);
        self::assertInstanceOf(\PDOStatement::class, $audits);
        self::assertSame(1, (int) $accounts->fetchColumn());
        self::assertSame(1, (int) $audits->fetchColumn());
    }

    public function testAdminCanRecordBalanceAdjustmentAndPublishClientVisibleSnapshot(): void
    {
        $client = $this->verifiedClient('client@example.test');
        $request = $this->portal->submitPlanRequest($client, ['investment_opportunity_uuid' => '22222222-2222-4222-8222-222222222222', 'risk_acknowledged' => true, 'requested_amount' => '2500', 'currency' => 'USD'], '127.0.0.1', null);
        $admin = new AdminUser(1, 'admin-uuid', 'admin@example.test', 'hash', 'Admin', 'active', ['ops'], ['client_plans.review', 'clients.view', 'client_balances.adjust', 'client_reports.publish']);
        $this->portal->review($request['uuid'], 'approved', 'Reviewed client request.', $admin, '127.0.0.1', 'req-3');
        $accountUuid = (string) $this->pdo->query('SELECT uuid FROM client_investment_accounts LIMIT 1')->fetchColumn();

        $adjustment = $this->portal->addBalanceAdjustment($accountUuid, [
            'adjustment_type' => 'increase',
            'amount' => '100.50',
            'currency' => 'USD',
            'effective_at' => '2026-09-13',
            'source_reference' => 'OPS-ADJ-1',
            'reason' => 'Admin-approved valuation update from external reporting record.',
            'idempotency_key' => 'adjustment-key-1',
        ], $admin, '127.0.0.1', 'req-4');
        self::assertSame('recorded', $adjustment['status']);

        $snapshot = $this->portal->addReportingSnapshot($accountUuid, [
            'snapshot_date' => '2026-09-13',
            'growth_amount' => '125.50',
            'currency' => 'USD',
            'methodology_note' => 'Approved reporting snapshot from external records.',
            'source_reference' => 'OPS-SNAP-1',
            'idempotency_key' => 'snapshot-key-1',
        ], $admin, '127.0.0.1', 'req-5');
        self::assertSame('published', $snapshot['status']);

        $visible = $this->portal->investmentSnapshots($client, $accountUuid);
        self::assertCount(1, $visible);
        self::assertSame('2500', (string) (float) $visible[0]['principal_amount']);
        self::assertSame('2625.50', $visible[0]['reported_value']);
        self::assertSame('5.0200', $visible[0]['growth_percent']);
        self::assertArrayNotHasKey('source_reference', $visible[0]);
        self::assertSame('2625.50', $this->pdo->query('SELECT current_balance FROM client_investment_accounts LIMIT 1')->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM client_audit_events WHERE action='admin.client_reporting_snapshot_published'")->fetchColumn());
    }

    private function registerClient(string $email): void
    {
        $this->identity->register(['first_name' => 'Ada', 'last_name' => 'Client', 'email' => $email, 'password' => 'Secure-client-123', 'password_confirmation' => 'Secure-client-123', 'consent_terms' => true], '127.0.0.1', null);
    }

    private function verifiedClient(string $email): \PerrymanFinance\Domain\ClientAccount\ClientUser
    {
        $this->registerClient($email);
        self::assertIsString($this->notifier->verificationUrl);
        parse_str((string) parse_url($this->notifier->verificationUrl, PHP_URL_QUERY), $query);
        $token = $query['token'] ?? null;
        self::assertIsString($token);
        $this->identity->verifyEmail($token, '127.0.0.1', null);
        $session = $this->identity->login($email, 'Secure-client-123', '127.0.0.1', null);
        self::assertNotEmpty($session['access']['access_token']);
        $client = $this->users->findByEmail($email);
        self::assertNotNull($client);
        return $client;
    }

    private function seed(): void
    {
        $this->pdo->exec("INSERT INTO admin_users VALUES (1,'admin-uuid','admin@example.test','hash','Admin','active',NULL,'2026-01-01','2026-01-01',NULL)");
        $this->pdo->exec("INSERT INTO investment_categories VALUES (1,'Managed Plans','managed-plans','',0,'2026-01-01','2026-01-01')");
        $this->pdo->exec("INSERT INTO investment_opportunities VALUES (1,'22222222-2222-4222-8222-222222222222',1,'Capital Preservation','capital-preservation','Short','Full','Strategy','Objective','Long term','moderate','$1,000','USD','published',0,NULL,'Risk applies','2026-01-01',1,1,'2026-01-01','2026-01-01',NULL)");
    }

    private function schema(): void
    {
        foreach (
            [
                'CREATE TABLE admin_users (id INTEGER PRIMARY KEY, uuid TEXT, email TEXT, password_hash TEXT, display_name TEXT, status TEXT, last_login_at TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
                'CREATE TABLE investment_categories (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, description TEXT, position INTEGER, created_at TEXT, updated_at TEXT)',
                'CREATE TABLE investment_opportunities (id INTEGER PRIMARY KEY, uuid TEXT, category_id INTEGER, title TEXT, slug TEXT, short_description TEXT, full_description TEXT, strategy_summary TEXT, investment_objective TEXT, investment_horizon TEXT, risk_classification TEXT, minimum_investment_display TEXT, currency_display TEXT, status TEXT, featured INTEGER, cover_media_id INTEGER, disclaimer TEXT, published_at TEXT, created_by INTEGER, updated_by INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
                'CREATE TABLE client_users (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, email TEXT, password_hash TEXT, status TEXT, email_verified_at TEXT, last_login_at TEXT, created_at TEXT, updated_at TEXT)',
                'CREATE TABLE client_profiles (client_user_id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, phone TEXT, country TEXT, consent_marketing INTEGER, consent_terms_at TEXT, created_at TEXT, updated_at TEXT)',
                'CREATE TABLE client_email_verification_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, client_user_id INTEGER, token_hash TEXT, expires_at TEXT, used_at TEXT, created_at TEXT)',
                'CREATE TABLE client_password_reset_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, client_user_id INTEGER, token_hash TEXT, expires_at TEXT, used_at TEXT, created_at TEXT)',
                'CREATE TABLE client_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, client_user_id INTEGER, token_hash TEXT, expires_at TEXT, revoked_at TEXT, replaced_by_id INTEGER, created_at TEXT)',
                'CREATE TABLE client_plan_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, client_user_id INTEGER, investment_opportunity_id INTEGER, requested_amount TEXT, currency TEXT, status TEXT, risk_acknowledged_at TEXT, client_note TEXT, reviewed_by INTEGER, reviewed_at TEXT, admin_note TEXT, created_at TEXT, updated_at TEXT)',
                'CREATE TABLE client_investment_accounts (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, client_user_id INTEGER, investment_opportunity_id INTEGER, plan_request_id INTEGER, status TEXT, approved_amount TEXT, current_balance TEXT, currency TEXT, approved_by INTEGER, approved_at TEXT, last_snapshot_at TEXT, created_at TEXT, updated_at TEXT)',
                'CREATE TABLE client_balance_adjustments (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, client_investment_account_id INTEGER, adjustment_type TEXT, amount TEXT, currency TEXT, effective_at TEXT, source_reference TEXT, reason TEXT, idempotency_key TEXT, created_by INTEGER, created_at TEXT)',
                'CREATE TABLE client_reporting_snapshots (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, client_investment_account_id INTEGER, snapshot_date TEXT, principal_amount TEXT, reported_value TEXT, growth_amount TEXT, growth_percent TEXT, currency TEXT, methodology_note TEXT, source_reference TEXT, idempotency_key TEXT, approved_by INTEGER, approved_at TEXT, created_at TEXT)',
                'CREATE TABLE client_audit_events (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_type TEXT, actor_id INTEGER, client_user_id INTEGER, action TEXT, entity_type TEXT, entity_id TEXT, old_values_json TEXT, new_values_json TEXT, ip_address TEXT, user_agent TEXT, request_id TEXT, created_at TEXT)',
                'CREATE TABLE rate_limit_counters (counter_key TEXT PRIMARY KEY, attempts INTEGER, window_started_at TEXT, expires_at TEXT)',
            ] as $sql
        ) {
            $this->pdo->exec($sql);
        }
    }
}
