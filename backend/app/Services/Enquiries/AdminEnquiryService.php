<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Enquiries;

use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Http\Exceptions\NotFoundException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\EnquiryRepository;

final readonly class AdminEnquiryService
{
    public const TRANSITIONS = [
        'new' => ['in_progress', 'spam', 'closed'],
        'in_progress' => ['resolved', 'spam', 'closed'],
        'resolved' => ['in_progress', 'closed'],
        'spam' => ['in_progress', 'closed'],
        'closed' => ['in_progress'],
    ];

    public function __construct(private EnquiryRepository $enquiries, private TransactionManager $transactions, private AuditLogRepository $audit)
    {
    }

    /** @param array<string,mixed> $query
     * @return array{items:list<array<string,mixed>>,meta:array<string,int>}
     */
    public function listing(array $query): array
    {
        $status = $query['status'] ?? '';
        $search = $query['search'] ?? '';
        if (!is_string($status) || ($status !== '' && !isset(self::TRANSITIONS[$status]))) {
            throw new ValidationException(['status' => ['Choose a supported status.']]);
        }
        if (!is_string($search) || strlen($search) > 255) {
            throw new ValidationException(['search' => ['Search must be at most 255 characters.']]);
        }
        $page = max(1, min(1000000, (int) ($query['page'] ?? 1)));
        $perPage = max(1, min(50, (int) ($query['per_page'] ?? 20)));
        $result = $this->enquiries->listing(trim($search), $status, $page, $perPage);
        return ['items' => $result['items'], 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total'], 'total_pages' => (int) ceil($result['total'] / $perPage)]];
    }

    /** @return array<string,mixed> */
    public function detail(string $uuid): array
    {
        return $this->enquiries->find($uuid) ?? throw new NotFoundException();
    }

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function update(string $uuid, array $input, int $actor, ?string $requestId): array
    {
        return $this->transactions->run(function () use ($uuid, $input, $actor, $requestId): array {
            $existing = $this->detail($uuid);
            $previous = (string) $existing['status'];
            $status = $input['status'] ?? null;
            if (array_diff(array_keys($input), ['status']) !== [] || !is_string($status) || !in_array($status, self::TRANSITIONS[$previous], true)) {
                throw new ValidationException(['status' => ['Choose an allowed status transition. Only status may be updated.']]);
            }
            $now = gmdate('Y-m-d H:i:s');
            if (!$this->enquiries->changeStatus($uuid, $previous, $status, $now)) {
                throw new ValidationException(['status' => ['The enquiry changed. Reload and try again.']]);
            }
            $this->audit->record($actor, 'enquiry.status_changed', ['subject_type' => 'enquiry', 'subject_id' => $uuid, 'previous_status' => $previous, 'status' => $status, 'request_id' => $requestId], $now);
            return $this->detail($uuid);
        });
    }
}
