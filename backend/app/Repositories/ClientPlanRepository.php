<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class ClientPlanRepository extends AbstractRepository
{
    /** @return list<array<string, mixed>> */
    public function eligiblePlans(): array
    {
        return array_values($this->execute(
            "SELECT i.uuid,i.title,i.slug,i.short_description,i.investment_objective,i.investment_horizon,i.risk_classification,i.minimum_investment_display,i.currency_display,i.disclaimer,c.name AS category_name FROM investment_opportunities i JOIN investment_categories c ON c.id=i.category_id WHERE i.deleted_at IS NULL AND i.status='published' AND i.published_at IS NOT NULL AND i.published_at<=:now ORDER BY i.published_at DESC,i.id DESC",
            ['now' => gmdate('Y-m-d H:i:s.u')],
        )->fetchAll());
    }

    public function findPublishedOpportunityId(string $uuid): ?int
    {
        $id = $this->execute(
            "SELECT id FROM investment_opportunities WHERE uuid=:uuid AND deleted_at IS NULL AND status='published' AND published_at IS NOT NULL AND published_at<=:now LIMIT 1",
            ['uuid' => $uuid, 'now' => gmdate('Y-m-d H:i:s.u')],
        )->fetchColumn();
        return is_numeric($id) ? (int) $id : null;
    }

    public function hasOpenRequest(int $clientUserId, int $opportunityId): bool
    {
        return (int) $this->execute("SELECT COUNT(*) FROM client_plan_requests WHERE client_user_id=:client AND investment_opportunity_id=:opportunity AND status='pending'", ['client' => $clientUserId, 'opportunity' => $opportunityId])->fetchColumn() > 0;
    }

    public function createRequest(int $clientUserId, int $opportunityId, string $uuid, ?string $amount, string $currency, string $note, string $now): int
    {
        $this->execute(
            "INSERT INTO client_plan_requests (uuid,client_user_id,investment_opportunity_id,requested_amount,currency,status,risk_acknowledged_at,client_note,created_at,updated_at) VALUES (:uuid,:client,:opportunity,:amount,:currency,'pending',:risk_acknowledged_at,:note,:created_at,:updated_at)",
            ['uuid' => $uuid, 'client' => $clientUserId, 'opportunity' => $opportunityId, 'amount' => $amount, 'currency' => $currency, 'note' => $note !== '' ? $note : null, 'risk_acknowledged_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        );
        return (int) $this->connection()->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function requestsForClient(int $clientUserId): array
    {
        return array_values($this->execute(
            'SELECT r.uuid,r.requested_amount,r.currency,r.status,r.risk_acknowledged_at,r.client_note,r.reviewed_at,r.created_at,r.updated_at,i.uuid AS plan_uuid,i.title AS plan_title,i.risk_classification FROM client_plan_requests r JOIN investment_opportunities i ON i.id=r.investment_opportunity_id WHERE r.client_user_id=:client ORDER BY r.created_at DESC',
            ['client' => $clientUserId],
        )->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function activeAccountsForClient(int $clientUserId): array
    {
        return array_values($this->execute(
            'SELECT a.uuid,a.status,a.approved_amount,a.current_balance,a.currency,a.approved_at,a.last_snapshot_at,i.title AS plan_title,i.risk_classification FROM client_investment_accounts a JOIN investment_opportunities i ON i.id=a.investment_opportunity_id WHERE a.client_user_id=:client ORDER BY a.approved_at DESC',
            ['client' => $clientUserId],
        )->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function findAccountByUuid(string $uuid): ?array
    {
        $row = $this->execute('SELECT * FROM client_investment_accounts WHERE uuid=:uuid LIMIT 1', ['uuid' => $uuid])->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findAccountForClient(string $uuid, int $clientUserId): ?array
    {
        $row = $this->execute(
            'SELECT a.uuid,a.status,a.approved_amount,a.current_balance,a.currency,a.approved_at,a.last_snapshot_at,i.title AS plan_title,i.risk_classification FROM client_investment_accounts a JOIN investment_opportunities i ON i.id=a.investment_opportunity_id WHERE a.uuid=:uuid AND a.client_user_id=:client LIMIT 1',
            ['uuid' => $uuid, 'client' => $clientUserId],
        )->fetch();
        return is_array($row) ? $row : null;
    }

    public function findAdjustmentByIdempotency(int $accountId, int $adminId, string $key): ?string
    {
        $uuid = $this->execute(
            'SELECT uuid FROM client_balance_adjustments WHERE client_investment_account_id=:account AND created_by=:admin AND idempotency_key=:key LIMIT 1',
            ['account' => $accountId, 'admin' => $adminId, 'key' => $key],
        )->fetchColumn();
        return is_string($uuid) ? $uuid : null;
    }

    public function createBalanceAdjustment(int $accountId, string $uuid, string $type, string $amount, string $currency, string $effectiveAt, string $sourceReference, string $reason, string $idempotencyKey, int $adminId, string $now): void
    {
        $this->execute(
            'INSERT INTO client_balance_adjustments (uuid,client_investment_account_id,adjustment_type,amount,currency,effective_at,source_reference,reason,idempotency_key,created_by,created_at) VALUES (:uuid,:account,:type,:amount,:currency,:effective_at,:source,:reason,:key,:admin,:now)',
            ['uuid' => $uuid, 'account' => $accountId, 'type' => $type, 'amount' => $amount, 'currency' => $currency, 'effective_at' => $effectiveAt, 'source' => $sourceReference, 'reason' => $reason, 'key' => $idempotencyKey, 'admin' => $adminId, 'now' => $now],
        );
    }

    public function updateCurrentBalance(int $accountId, string $balance, string $now): void
    {
        $this->execute('UPDATE client_investment_accounts SET current_balance=:balance,updated_at=:now WHERE id=:id', ['id' => $accountId, 'balance' => $balance, 'now' => $now]);
    }

    public function findSnapshotByIdempotency(int $accountId, int $adminId, string $key): ?string
    {
        $uuid = $this->execute(
            'SELECT uuid FROM client_reporting_snapshots WHERE client_investment_account_id=:account AND approved_by=:admin AND idempotency_key=:key LIMIT 1',
            ['account' => $accountId, 'admin' => $adminId, 'key' => $key],
        )->fetchColumn();
        return is_string($uuid) ? $uuid : null;
    }

    /** @param array<string, string|int> $snapshot */
    public function createReportingSnapshot(array $snapshot): void
    {
        $this->execute(
            'INSERT INTO client_reporting_snapshots (uuid,client_investment_account_id,snapshot_date,principal_amount,reported_value,growth_amount,growth_percent,currency,methodology_note,source_reference,idempotency_key,approved_by,approved_at,created_at) VALUES (:uuid,:account,:snapshot_date,:principal,:reported,:growth,:percent,:currency,:methodology,:source,:key,:admin,:approved_at,:created_at)',
            $snapshot,
        );
        $this->execute(
            'UPDATE client_investment_accounts SET current_balance=:reported,last_snapshot_at=:approved_at,updated_at=:created_at WHERE id=:account',
            ['account' => $snapshot['account'], 'reported' => $snapshot['reported'], 'approved_at' => $snapshot['approved_at'], 'created_at' => $snapshot['created_at']],
        );
    }

    /** @return list<array<string, mixed>> */
    public function snapshotsForClientAccount(string $accountUuid, int $clientUserId): array
    {
        return array_values($this->execute(
            'SELECT s.uuid,s.snapshot_date,s.principal_amount,s.reported_value,s.growth_amount,s.growth_percent,s.currency,s.methodology_note,s.approved_at FROM client_reporting_snapshots s JOIN client_investment_accounts a ON a.id=s.client_investment_account_id WHERE a.uuid=:uuid AND a.client_user_id=:client ORDER BY s.snapshot_date DESC,s.id DESC',
            ['uuid' => $accountUuid, 'client' => $clientUserId],
        )->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function adjustmentsForAccount(int $accountId): array
    {
        return array_values($this->execute(
            'SELECT uuid,adjustment_type,amount,currency,effective_at,source_reference,reason,created_at FROM client_balance_adjustments WHERE client_investment_account_id=:account ORDER BY effective_at DESC,id DESC',
            ['account' => $accountId],
        )->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function snapshotsForAccount(int $accountId): array
    {
        return array_values($this->execute(
            'SELECT uuid,snapshot_date,principal_amount,reported_value,growth_amount,growth_percent,currency,methodology_note,source_reference,approved_at FROM client_reporting_snapshots WHERE client_investment_account_id=:account ORDER BY snapshot_date DESC,id DESC',
            ['account' => $accountId],
        )->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function listRequests(?string $status): array
    {
        $where = $status !== null ? 'WHERE r.status=:status' : '';
        $params = $status !== null ? ['status' => $status] : [];
        return array_values($this->execute(
            "SELECT r.uuid,r.requested_amount,r.currency,r.status,r.created_at,r.reviewed_at,u.uuid AS client_uuid,u.email,p.first_name,p.last_name,i.title AS plan_title,i.risk_classification FROM client_plan_requests r JOIN client_users u ON u.id=r.client_user_id LEFT JOIN client_profiles p ON p.client_user_id=u.id JOIN investment_opportunities i ON i.id=r.investment_opportunity_id {$where} ORDER BY r.created_at DESC LIMIT 100",
            $params,
        )->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function findRequestByUuid(string $uuid): ?array
    {
        $row = $this->execute('SELECT * FROM client_plan_requests WHERE uuid=:uuid LIMIT 1', ['uuid' => $uuid])->fetch();
        return is_array($row) ? $row : null;
    }

    public function reviewRequest(int $id, string $status, int $adminId, string $reason, string $now): void
    {
        $this->execute('UPDATE client_plan_requests SET status=:status,reviewed_by=:admin,reviewed_at=:reviewed_at,admin_note=:reason,updated_at=:updated_at WHERE id=:id AND status=\'pending\'', ['id' => $id, 'status' => $status, 'admin' => $adminId, 'reason' => $reason, 'reviewed_at' => $now, 'updated_at' => $now]);
    }

    /** @param array<string, mixed> $request */
    public function createAccountForApprovedRequest(array $request, string $uuid, string $now): void
    {
        $this->execute(
            "INSERT INTO client_investment_accounts (uuid,client_user_id,investment_opportunity_id,plan_request_id,status,approved_amount,current_balance,currency,approved_by,approved_at,created_at,updated_at) VALUES (:uuid,:client,:opportunity,:request,'active',:approved_amount,:current_balance,:currency,:admin,:approved_at,:created_at,:updated_at)",
            ['uuid' => $uuid, 'client' => (int) $request['client_user_id'], 'opportunity' => (int) $request['investment_opportunity_id'], 'request' => (int) $request['id'], 'approved_amount' => $request['requested_amount'], 'current_balance' => $request['requested_amount'], 'currency' => $request['currency'], 'admin' => (int) $request['reviewed_by'], 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        );
    }
}
