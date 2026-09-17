<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Seeders;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class DemoClientInvestmentSeeder implements Seeder
{
    private const PASSWORD = 'Client-demo-123';

    public function name(): string
    {
        return 'demo_client_investment_records';
    }

    public function run(PDO $connection): void
    {
        $now = $this->now();
        $adminId = $this->adminId($connection, $now);
        $categoryId = $this->categoryId($connection, $now);
        $plans = [
            'pf-preservation-demo' => $this->upsertPlan($connection, $categoryId, 'pf-preservation-demo', 'Perryman Capital Preservation Plan', 'perryman-capital-preservation-plan', 'A conservative reporting demo plan for capital preservation.', 'moderate', '$1,000', 'USD', $adminId, $now),
            'pf-growth-demo' => $this->upsertPlan($connection, $categoryId, 'pf-growth-demo', 'Perryman Balanced Growth Plan', 'perryman-balanced-growth-plan', 'A balanced reporting demo plan for long-term managed growth.', 'moderate_high', '$5,000', 'USD', $adminId, $now),
            'pf-digital-demo' => $this->upsertPlan($connection, $categoryId, 'pf-digital-demo', 'Perryman Digital Asset Reporting Plan', 'perryman-digital-asset-reporting-plan', 'A digital asset reporting demo plan with enhanced risk disclosure.', 'high', '$2,500', 'USD', $adminId, $now),
        ];

        $adaId = $this->upsertClient($connection, 'demo.client.ada@example.test', 'Ada', 'Okafor', 'Nigeria', $now);
        $benId = $this->upsertClient($connection, 'demo.client.ben@example.test', 'Benjamin', 'Reed', 'United States', $now);
        $chiomaId = $this->upsertClient($connection, 'demo.client.chioma@example.test', 'Chioma', 'Adebayo', 'Nigeria', $now);

        $this->upsertPlanRequest($connection, 'demo-ada-plan-request', $adaId, $plans['pf-preservation-demo'], '5000.00', 'pending', null, null, $now);
        $benRequestId = $this->upsertPlanRequest($connection, 'demo-ben-plan-request', $benId, $plans['pf-growth-demo'], '15000.00', 'approved', $adminId, 'Approved demo request for reporting preview.', $now);
        $chiomaRequestId = $this->upsertPlanRequest($connection, 'demo-chioma-plan-request', $chiomaId, $plans['pf-digital-demo'], '25000.00', 'approved', $adminId, 'Approved demo request with published growth snapshots.', $now);

        $this->upsertInvestmentAccount($connection, 'demo-ben-investment', $benId, $plans['pf-growth-demo'], $benRequestId, '15000.00', '15000.00', 'USD', $adminId, null, $now);
        $chiomaAccountId = $this->upsertInvestmentAccount($connection, 'demo-chioma-investment', $chiomaId, $plans['pf-digital-demo'], $chiomaRequestId, '25000.00', '27450.75', 'USD', $adminId, $now, $now);

        $this->upsertAdjustment($connection, 'demo-chioma-adjustment-1', $chiomaAccountId, 'valuation_update', '27450.75', 'USD', '2026-09-01 00:00:00.000000', 'DEMO-VALUATION-2026-09', 'Demo valuation update from approved reporting records.', 'demo-adjustment-1', $adminId, $now);
        $this->upsertSnapshot($connection, 'demo-chioma-snapshot-1', $chiomaAccountId, '2026-08-01', '25000.00', '26325.00', '1325.00', '5.3000', 'USD', 'Demo approved monthly reporting snapshot.', 'DEMO-SNAPSHOT-2026-08', 'demo-snapshot-1', $adminId, '2026-08-01 12:00:00.000000', $now);
        $this->upsertSnapshot($connection, 'demo-chioma-snapshot-2', $chiomaAccountId, '2026-09-01', '25000.00', '27450.75', '2450.75', '9.8030', 'USD', 'Demo approved monthly reporting snapshot.', 'DEMO-SNAPSHOT-2026-09', 'demo-snapshot-2', $adminId, $now, $now);
    }

    private function adminId(PDO $connection, string $now): int
    {
        $id = $connection->query("SELECT id FROM admin_users WHERE email='admin@example.test' LIMIT 1")?->fetchColumn();
        if (is_numeric($id)) {
            return (int) $id;
        }
        $statement = $connection->prepare('INSERT INTO admin_users (uuid,email,password_hash,display_name,status,created_at,updated_at) VALUES (:uuid,:email,:password,:name,\'active\',:created_at,:updated_at)');
        $statement->execute([
            'uuid' => $this->uuid('demo-admin-fallback'),
            'email' => 'admin@example.test',
            'password' => password_hash('Correct-password-123', PASSWORD_DEFAULT),
            'name' => 'Development Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $connection->lastInsertId();
    }

    private function categoryId(PDO $connection, string $now): int
    {
        $statement = $connection->prepare('INSERT INTO investment_categories (name,slug,description,position,created_at,updated_at) VALUES (\'Demo Client Plans\',\'demo-client-plans\',\'Development-only client investment reporting plans.\',99,:created_at,:updated_at) ON DUPLICATE KEY UPDATE description=VALUES(description),updated_at=VALUES(updated_at)');
        $statement->execute(['created_at' => $now, 'updated_at' => $now]);
        return (int) $connection->query("SELECT id FROM investment_categories WHERE slug='demo-client-plans' LIMIT 1")->fetchColumn();
    }

    private function upsertPlan(PDO $connection, int $categoryId, string $key, string $title, string $slug, string $short, string $risk, string $minimum, string $currency, int $adminId, string $now): int
    {
        $statement = $connection->prepare('INSERT INTO investment_opportunities (uuid,category_id,title,slug,short_description,full_description,strategy_summary,investment_objective,investment_horizon,risk_classification,minimum_investment_display,currency_display,status,featured,cover_media_id,disclaimer,published_at,created_by,updated_by,created_at,updated_at,deleted_at) VALUES (:uuid,:category,:title,:slug,:short,:full,:strategy,:objective,\'Long term\',:risk,:minimum,:currency,\'published\',0,NULL,:disclaimer,:published_at,:created_by,:updated_by,:created_at,:updated_at,NULL) ON DUPLICATE KEY UPDATE title=VALUES(title),category_id=VALUES(category_id),short_description=VALUES(short_description),risk_classification=VALUES(risk_classification),status=\'published\',published_at=VALUES(published_at),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at),deleted_at=NULL');
        $statement->execute([
            'uuid' => $this->uuid($key),
            'category' => $categoryId,
            'title' => $title,
            'slug' => $slug,
            'short' => $short,
            'full' => $short . ' This development record exists so client dashboards can be previewed with approved reporting values.',
            'strategy' => 'Demo managed reporting strategy.',
            'objective' => 'Show a client-safe account reporting workflow without deposits, withdrawals, custody, trading, or guaranteed returns.',
            'risk' => $risk,
            'minimum' => $minimum,
            'currency' => $currency,
            'disclaimer' => 'Demo reporting plan only. Capital is at risk and future returns are not guaranteed.',
            'published_at' => $now,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $connection->query("SELECT id FROM investment_opportunities WHERE uuid=" . $connection->quote($this->uuid($key)) . ' LIMIT 1')->fetchColumn();
    }

    private function upsertClient(PDO $connection, string $email, string $first, string $last, string $country, string $now): int
    {
        $statement = $connection->prepare('INSERT INTO client_users (uuid,email,password_hash,status,email_verified_at,created_at,updated_at) VALUES (:uuid,:email,:password,\'active\',:verified_at,:created_at,:updated_at) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),status=\'active\',email_verified_at=COALESCE(email_verified_at, VALUES(email_verified_at)),updated_at=VALUES(updated_at)');
        $statement->execute([
            'uuid' => $this->uuid('client-' . $email),
            'email' => $email,
            'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $clientId = (int) $connection->query('SELECT id FROM client_users WHERE email=' . $connection->quote($email) . ' LIMIT 1')->fetchColumn();
        $profile = $connection->prepare('INSERT INTO client_profiles (client_user_id,first_name,last_name,phone,country,consent_marketing,consent_terms_at,created_at,updated_at) VALUES (:client,:first,:last,NULL,:country,0,:consent_at,:created_at,:updated_at) ON DUPLICATE KEY UPDATE first_name=VALUES(first_name),last_name=VALUES(last_name),country=VALUES(country),updated_at=VALUES(updated_at)');
        $profile->execute(['client' => $clientId, 'first' => $first, 'last' => $last, 'country' => $country, 'consent_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        return $clientId;
    }

    private function upsertPlanRequest(PDO $connection, string $key, int $clientId, int $planId, string $amount, string $status, ?int $adminId, ?string $adminNote, string $now): int
    {
        $reviewedAt = $status === 'pending' ? null : $now;
        $statement = $connection->prepare('INSERT INTO client_plan_requests (uuid,client_user_id,investment_opportunity_id,requested_amount,currency,status,risk_acknowledged_at,client_note,reviewed_by,reviewed_at,admin_note,created_at,updated_at) VALUES (:uuid,:client,:plan,:amount,\'USD\',:status,:risk_at,\'Demo client request.\',:admin,:reviewed_at,:admin_note,:created_at,:updated_at) ON DUPLICATE KEY UPDATE status=VALUES(status),reviewed_by=VALUES(reviewed_by),reviewed_at=VALUES(reviewed_at),admin_note=VALUES(admin_note),updated_at=VALUES(updated_at)');
        $statement->execute(['uuid' => $this->uuid($key), 'client' => $clientId, 'plan' => $planId, 'amount' => $amount, 'status' => $status, 'admin' => $adminId, 'reviewed_at' => $reviewedAt, 'admin_note' => $adminNote, 'risk_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        return (int) $connection->query('SELECT id FROM client_plan_requests WHERE uuid=' . $connection->quote($this->uuid($key)) . ' LIMIT 1')->fetchColumn();
    }

    private function upsertInvestmentAccount(PDO $connection, string $key, int $clientId, int $planId, int $requestId, string $approvedAmount, string $currentBalance, string $currency, int $adminId, ?string $lastSnapshotAt, string $now): int
    {
        $statement = $connection->prepare('INSERT INTO client_investment_accounts (uuid,client_user_id,investment_opportunity_id,plan_request_id,status,approved_amount,current_balance,currency,approved_by,approved_at,last_snapshot_at,created_at,updated_at) VALUES (:uuid,:client,:plan,:request,\'active\',:approved,:balance,:currency,:admin,:approved_at,:last_snapshot,:created_at,:updated_at) ON DUPLICATE KEY UPDATE approved_amount=VALUES(approved_amount),current_balance=VALUES(current_balance),currency=VALUES(currency),last_snapshot_at=VALUES(last_snapshot_at),updated_at=VALUES(updated_at)');
        $statement->execute(['uuid' => $this->uuid($key), 'client' => $clientId, 'plan' => $planId, 'request' => $requestId, 'approved' => $approvedAmount, 'balance' => $currentBalance, 'currency' => $currency, 'admin' => $adminId, 'last_snapshot' => $lastSnapshotAt, 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        return (int) $connection->query('SELECT id FROM client_investment_accounts WHERE uuid=' . $connection->quote($this->uuid($key)) . ' LIMIT 1')->fetchColumn();
    }

    private function upsertAdjustment(PDO $connection, string $key, int $accountId, string $type, string $amount, string $currency, string $effectiveAt, string $source, string $reason, string $idempotencyKey, int $adminId, string $now): void
    {
        $statement = $connection->prepare('INSERT INTO client_balance_adjustments (uuid,client_investment_account_id,adjustment_type,amount,currency,effective_at,source_reference,reason,idempotency_key,created_by,created_at) VALUES (:uuid,:account,:type,:amount,:currency,:effective_at,:source,:reason,:key,:admin,:now) ON DUPLICATE KEY UPDATE amount=VALUES(amount),source_reference=VALUES(source_reference),reason=VALUES(reason)');
        $statement->execute(['uuid' => $this->uuid($key), 'account' => $accountId, 'type' => $type, 'amount' => $amount, 'currency' => $currency, 'effective_at' => $effectiveAt, 'source' => $source, 'reason' => $reason, 'key' => $idempotencyKey, 'admin' => $adminId, 'now' => $now]);
    }

    private function upsertSnapshot(PDO $connection, string $key, int $accountId, string $snapshotDate, string $principal, string $reported, string $growth, string $percent, string $currency, string $methodology, string $source, string $idempotencyKey, int $adminId, string $approvedAt, string $now): void
    {
        $statement = $connection->prepare('INSERT INTO client_reporting_snapshots (uuid,client_investment_account_id,snapshot_date,principal_amount,reported_value,growth_amount,growth_percent,currency,methodology_note,source_reference,idempotency_key,approved_by,approved_at,created_at) VALUES (:uuid,:account,:snapshot_date,:principal,:reported,:growth,:percent,:currency,:methodology,:source,:key,:admin,:approved_at,:now) ON DUPLICATE KEY UPDATE reported_value=VALUES(reported_value),growth_amount=VALUES(growth_amount),growth_percent=VALUES(growth_percent),methodology_note=VALUES(methodology_note),source_reference=VALUES(source_reference)');
        $statement->execute(['uuid' => $this->uuid($key), 'account' => $accountId, 'snapshot_date' => $snapshotDate, 'principal' => $principal, 'reported' => $reported, 'growth' => $growth, 'percent' => $percent, 'currency' => $currency, 'methodology' => $methodology, 'source' => $source, 'key' => $idempotencyKey, 'admin' => $adminId, 'approved_at' => $approvedAt, 'now' => $now]);
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    private function uuid(string $key): string
    {
        $hash = md5('perrymanfinance-demo-' . $key);
        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-4' . substr($hash, 13, 3) . '-a' . substr($hash, 17, 3) . '-' . substr($hash, 20, 12);
    }
}
