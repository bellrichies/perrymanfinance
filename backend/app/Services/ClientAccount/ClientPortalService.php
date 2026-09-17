<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\ClientAccount;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\ClientAccount\ClientUser;
use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\Exceptions\BadRequestException;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\ClientAuditRepository;
use PerrymanFinance\Repositories\ClientPlanRepository;
use PerrymanFinance\Repositories\ClientUserRepository;

final readonly class ClientPortalService
{
    public function __construct(
        private ClientPlanRepository $plans,
        private ClientUserRepository $users,
        private ClientAuditRepository $audit,
        private TransactionManager $transactions,
    ) {
    }

    /** @return array<string, mixed> */
    public function dashboard(ClientUser $client): array
    {
        $accounts = $this->plans->activeAccountsForClient($client->id);
        $requests = $this->plans->requestsForClient($client->id);
        $reportedBalance = 0.0;
        $primaryCurrency = null;
        foreach ($accounts as $account) {
            $primaryCurrency ??= is_string($account['currency'] ?? null) ? (string) $account['currency'] : null;
            if (($account['currency'] ?? null) === $primaryCurrency && is_numeric($account['current_balance'] ?? null)) {
                $reportedBalance += (float) $account['current_balance'];
            }
        }
        $latestSnapshots = array_map(fn (array $account): array => [
            'account_uuid' => $account['uuid'],
            'plan_title' => $account['plan_title'],
            'snapshots' => array_slice($this->plans->snapshotsForClientAccount((string) $account['uuid'], $client->id), 0, 3),
        ], $accounts);
        return [
            'client' => $client->publicData(),
            'summary' => [
                'active_investments' => count($accounts),
                'pending_requests' => count(array_filter($requests, static fn (array $request): bool => ($request['status'] ?? null) === 'pending')),
                'reported_balance' => count($accounts) > 0 ? number_format($reportedBalance, 2, '.', '') : null,
                'currency' => $primaryCurrency,
                'latest_snapshot_date' => $this->latestSnapshotDate($latestSnapshots),
            ],
            'plan_requests' => $requests,
            'investment_accounts' => $accounts,
            'latest_snapshots' => $latestSnapshots,
            'disclaimer' => 'Balances and approvals shown here are account reporting records only. They are not wallets, withdrawable cash, deposits, trades, custody records, or guarantees of future returns.',
        ];
    }

    /** @return list<array<string, mixed>> */
    public function eligiblePlans(): array
    {
        return $this->plans->eligiblePlans();
    }

    /** @param array<string, mixed> $input */
    /**
     * @param array<string, mixed> $input
     * @return array{uuid: string, status: string}
     */
    public function submitPlanRequest(ClientUser $client, array $input, string $ip, ?string $requestId): array
    {
        $planUuid = $this->required($input['investment_opportunity_uuid'] ?? null, 'investment_opportunity_uuid');
        if (empty($input['risk_acknowledged'])) {
            throw new ValidationException(['risk_acknowledged' => ['You must acknowledge the risk and non-guarantee disclosure before submitting a plan request.']]);
        }
        $opportunityId = $this->plans->findPublishedOpportunityId($planUuid);
        if ($opportunityId === null) {
            throw new NotFoundException();
        }
        if ($this->plans->hasOpenRequest($client->id, $opportunityId)) {
            throw new ValidationException(['investment_opportunity_uuid' => ['You already have a pending request for this plan.']]);
        }
        $amount = $this->optionalAmount($input['requested_amount'] ?? null);
        $currency = strtoupper($this->required($input['currency'] ?? 'USD', 'currency'));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new ValidationException(['currency' => ['Use a valid three-letter currency code.']]);
        }
        $note = is_string($input['client_note'] ?? null) ? trim($input['client_note']) : '';
        $now = $this->now();
        $uuid = $this->uuid();
        $this->plans->createRequest($client->id, $opportunityId, $uuid, $amount, $currency, $note, $now);
        $this->audit->record('client', $client->id, $client->id, 'client.plan_request_created', ['entity_type' => 'client_plan_request', 'entity_id' => $uuid, 'ip_address' => $ip, 'request_id' => $requestId, 'values' => ['currency' => $currency, 'has_amount' => $amount !== null]], $now);
        return ['uuid' => $uuid, 'status' => 'pending'];
    }

    /** @return list<array<string, mixed>> */
    public function adminClients(string $search, AdminUser $admin, string $ip, ?string $requestId): array
    {
        $this->audit->record('admin', $admin->id, null, 'admin.client_list_viewed', ['ip_address' => $ip, 'request_id' => $requestId], $this->now());
        return $this->users->list($search);
    }

    /** @return array<string, mixed> */
    public function adminClientDetail(string $uuid, AdminUser $admin, string $ip, ?string $requestId): array
    {
        $client = $this->users->findByUuid($uuid);
        if ($client === null) {
            throw new NotFoundException();
        }
        $clientUser = $this->users->findByEmail((string) $client['email']);
        $clientId = $clientUser?->id;
        $this->audit->record('admin', $admin->id, $clientId, 'admin.client_record_viewed', ['entity_type' => 'client_user', 'entity_id' => $uuid, 'ip_address' => $ip, 'request_id' => $requestId], $this->now());
        return [
            'client' => $client,
            'plan_requests' => $clientId !== null ? $this->plans->requestsForClient($clientId) : [],
            'investment_accounts' => $clientId !== null ? $this->plans->activeAccountsForClient($clientId) : [],
            'audit_events' => $clientId !== null ? $this->audit->forClient($clientId) : [],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function adminPlanRequests(?string $status): array
    {
        return $this->plans->listRequests($status);
    }

    /** @return array{uuid: string, status: string} */
    public function review(string $uuid, string $decision, string $reason, AdminUser $admin, string $ip, ?string $requestId): array
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new BadRequestException('Unsupported review decision.');
        }
        if (trim($reason) === '') {
            throw new ValidationException(['reason' => ['A review reason is required.']]);
        }
        return $this->transactions->run(function () use ($uuid, $decision, $reason, $admin, $ip, $requestId): array {
            $request = $this->plans->findRequestByUuid($uuid);
            if ($request === null) {
                throw new NotFoundException();
            }
            if ($request['status'] !== 'pending') {
                throw new ValidationException(['status' => ['Only pending plan requests can be reviewed.']]);
            }
            $now = $this->now();
            $request['reviewed_by'] = $admin->id;
            $this->plans->reviewRequest((int) $request['id'], $decision, $admin->id, trim($reason), $now);
            if ($decision === 'approved') {
                $this->plans->createAccountForApprovedRequest($request, $this->uuid(), $now);
            }
            $this->audit->record('admin', $admin->id, (int) $request['client_user_id'], 'admin.client_plan_request_' . $decision, ['entity_type' => 'client_plan_request', 'entity_id' => $uuid, 'ip_address' => $ip, 'request_id' => $requestId, 'values' => ['reason' => trim($reason)]], $now);
            return ['uuid' => $uuid, 'status' => $decision];
        });
    }

    /** @return list<array<string, mixed>> */
    public function investmentSnapshots(ClientUser $client, string $accountUuid): array
    {
        if ($this->plans->findAccountForClient($accountUuid, $client->id) === null) {
            throw new NotFoundException();
        }
        return $this->plans->snapshotsForClientAccount($accountUuid, $client->id);
    }

    /** @param array<string, mixed> $input */
    /** @return array{uuid: string, status: string} */
    public function addBalanceAdjustment(string $accountUuid, array $input, AdminUser $admin, string $ip, ?string $requestId): array
    {
        return $this->transactions->run(function () use ($accountUuid, $input, $admin, $ip, $requestId): array {
            $account = $this->adminAccount($accountUuid);
            $key = $this->idempotencyKey($input);
            $existing = $this->plans->findAdjustmentByIdempotency((int) $account['id'], $admin->id, $key);
            if ($existing !== null) {
                return ['uuid' => $existing, 'status' => 'already_recorded'];
            }
            $type = $this->enum($input['adjustment_type'] ?? null, 'adjustment_type', ['initial_allocation', 'increase', 'decrease', 'correction', 'valuation_update']);
            $amount = $this->positiveAmount($input['amount'] ?? null, 'amount');
            $currency = $this->currency($input['currency'] ?? $account['currency']);
            if ($currency !== $account['currency']) {
                throw new ValidationException(['currency' => ['Adjustment currency must match the investment account currency.']]);
            }
            $effectiveAt = $this->dateTime($input['effective_at'] ?? null, 'effective_at');
            $source = $this->requiredText($input['source_reference'] ?? null, 'source_reference', 255);
            $reason = $this->requiredText($input['reason'] ?? null, 'reason', 2000);
            $current = $account['current_balance'] ?? $account['approved_amount'] ?? '0.00';
            $next = $type === 'decrease'
                ? (float) $current - (float) $amount
                : (in_array($type, ['correction', 'valuation_update'], true) ? (float) $amount : (float) $current + (float) $amount);
            if ($next < 0) {
                throw new ValidationException(['amount' => ['Adjustment would create a negative reported balance.']]);
            }
            $now = $this->now();
            $uuid = $this->uuid();
            $this->plans->createBalanceAdjustment((int) $account['id'], $uuid, $type, $amount, $currency, $effectiveAt, $source, $reason, $key, $admin->id, $now);
            $this->plans->updateCurrentBalance((int) $account['id'], number_format($next, 2, '.', ''), $now);
            $this->audit->record('admin', $admin->id, (int) $account['client_user_id'], 'admin.client_balance_adjusted', ['entity_type' => 'client_balance_adjustment', 'entity_id' => $uuid, 'ip_address' => $ip, 'request_id' => $requestId, 'values' => ['type' => $type, 'amount' => $amount, 'currency' => $currency, 'source_reference' => $source]], $now);
            return ['uuid' => $uuid, 'status' => 'recorded'];
        });
    }

    /** @param array<string, mixed> $input */
    /** @return array{uuid: string, status: string} */
    public function addReportingSnapshot(string $accountUuid, array $input, AdminUser $admin, string $ip, ?string $requestId): array
    {
        return $this->transactions->run(function () use ($accountUuid, $input, $admin, $ip, $requestId): array {
            $account = $this->adminAccount($accountUuid);
            $key = $this->idempotencyKey($input);
            $existing = $this->plans->findSnapshotByIdempotency((int) $account['id'], $admin->id, $key);
            if ($existing !== null) {
                return ['uuid' => $existing, 'status' => 'already_published'];
            }
            $currency = $this->currency($input['currency'] ?? $account['currency']);
            if ($currency !== $account['currency']) {
                throw new ValidationException(['currency' => ['Snapshot currency must match the investment account currency.']]);
            }
            $principal = $this->accountPrincipal($account);
            $growth = $this->decimal($input['growth_amount'] ?? null, 'growth_amount', false);
            $reported = number_format((float) $principal + (float) $growth, 2, '.', '');
            if ((float) $reported <= 0) {
                throw new ValidationException(['growth_amount' => ['Growth amount would create a non-positive reported value.']]);
            }
            $percent = number_format(((float) $growth / (float) $principal) * 100, 4, '.', '');
            $date = $this->date($input['snapshot_date'] ?? null, 'snapshot_date');
            $methodology = $this->requiredText($input['methodology_note'] ?? null, 'methodology_note', 2000);
            $source = $this->requiredText($input['source_reference'] ?? null, 'source_reference', 255);
            $now = $this->now();
            $uuid = $this->uuid();
            $this->plans->createReportingSnapshot([
                'uuid' => $uuid,
                'account' => (int) $account['id'],
                'snapshot_date' => $date,
                'principal' => $principal,
                'reported' => $reported,
                'growth' => $growth,
                'percent' => $percent,
                'currency' => $currency,
                'methodology' => $methodology,
                'source' => $source,
                'key' => $key,
                'admin' => $admin->id,
                'approved_at' => $now,
                'created_at' => $now,
            ]);
            $this->audit->record('admin', $admin->id, (int) $account['client_user_id'], 'admin.client_reporting_snapshot_published', ['entity_type' => 'client_reporting_snapshot', 'entity_id' => $uuid, 'ip_address' => $ip, 'request_id' => $requestId, 'values' => ['snapshot_date' => $date, 'reported_value' => $reported, 'currency' => $currency]], $now);
            return ['uuid' => $uuid, 'status' => 'published'];
        });
    }

    /** @return array<string, mixed> */
    public function adminInvestmentDetail(string $accountUuid): array
    {
        $account = $this->adminAccount($accountUuid);
        return [
            'account' => $account,
            'adjustments' => $this->plans->adjustmentsForAccount((int) $account['id']),
            'snapshots' => $this->plans->snapshotsForAccount((int) $account['id']),
        ];
    }

    /** @param array<string, mixed> $account */
    private function accountPrincipal(array $account): string
    {
        $principal = $account['approved_amount'] ?? null;
        if (!is_numeric($principal) || (float) $principal <= 0) {
            throw new ValidationException(['approved_amount' => ['The investment account needs a positive approved amount before publishing a snapshot.']]);
        }
        return number_format((float) $principal, 2, '.', '');
    }

    private function required(mixed $value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException([$field => ['This field is required.']]);
        }
        return trim($value);
    }

    private function optionalAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value) || (float) $value <= 0) {
            throw new ValidationException(['requested_amount' => ['Enter a positive amount or leave it blank.']]);
        }
        return number_format((float) $value, 2, '.', '');
    }

    /** @return array<string, mixed> */
    private function adminAccount(string $uuid): array
    {
        $account = $this->plans->findAccountByUuid($uuid);
        if ($account === null) {
            throw new NotFoundException();
        }
        return $account;
    }

    /** @param list<string> $allowed */
    private function enum(mixed $value, string $field, array $allowed): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw new ValidationException([$field => ['Select a supported value.']]);
        }
        return $value;
    }

    private function idempotencyKey(array $input): string
    {
        return $this->requiredText($input['idempotency_key'] ?? null, 'idempotency_key', 128);
    }

    private function requiredText(mixed $value, string $field, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > $max) {
            throw new ValidationException([$field => ['This field is required.']]);
        }
        return trim($value);
    }

    private function positiveAmount(mixed $value, string $field): string
    {
        return $this->decimal($value, $field, true);
    }

    private function decimal(mixed $value, string $field, bool $positive, int $scale = 2): string
    {
        if (!is_numeric($value) || ($positive && (float) $value <= 0)) {
            throw new ValidationException([$field => ['Enter a valid amount.']]);
        }
        return number_format((float) $value, $scale, '.', '');
    }

    private function currency(mixed $value): string
    {
        $currency = strtoupper($this->requiredText($value, 'currency', 3));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new ValidationException(['currency' => ['Use a valid three-letter currency code.']]);
        }
        return $currency;
    }

    private function date(mixed $value, string $field): string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new ValidationException([$field => ['Use YYYY-MM-DD format.']]);
        }
        return $value;
    }

    private function dateTime(mixed $value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException([$field => ['This field is required.']]);
        }
        $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    /** @param list<array{snapshots: list<array<string, mixed>>}> $snapshotGroups */
    private function latestSnapshotDate(array $snapshotGroups): ?string
    {
        $latest = null;
        foreach ($snapshotGroups as $group) {
            foreach ($group['snapshots'] as $snapshot) {
                $date = is_string($snapshot['snapshot_date'] ?? null) ? $snapshot['snapshot_date'] : null;
                if ($date !== null && ($latest === null || $date > $latest)) {
                    $latest = $date;
                }
            }
        }
        return $latest;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
