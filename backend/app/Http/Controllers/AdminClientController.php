<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\ClientAccount\ClientPortalService;

final readonly class AdminClientController
{
    public function __construct(private ClientPortalService $portal, private ApiResponseFactory $responses)
    {
    }

    public function clients(Request $request): Response
    {
        $search = $request->input('search', '');
        return $this->responses->success($this->portal->adminClients(is_string($search) ? trim($search) : '', $this->admin($request), $this->ip($request), $this->requestId($request)));
    }

    public function client(Request $request): Response
    {
        return $this->responses->success($this->portal->adminClientDetail((string) $request->route('uuid'), $this->admin($request), $this->ip($request), $this->requestId($request)));
    }

    public function planRequests(Request $request): Response
    {
        $status = $request->input('status');
        return $this->responses->success($this->portal->adminPlanRequests(is_string($status) && $status !== '' ? $status : null));
    }

    public function approve(Request $request): Response
    {
        return $this->responses->success($this->portal->review((string) $request->route('uuid'), 'approved', $this->reason($request), $this->admin($request), $this->ip($request), $this->requestId($request)), [], 'Plan request approved.');
    }

    public function reject(Request $request): Response
    {
        return $this->responses->success($this->portal->review((string) $request->route('uuid'), 'rejected', $this->reason($request), $this->admin($request), $this->ip($request), $this->requestId($request)), [], 'Plan request rejected.');
    }

    public function investment(Request $request): Response
    {
        return $this->responses->success($this->portal->adminInvestmentDetail((string) $request->route('uuid')));
    }

    public function balanceAdjustment(Request $request): Response
    {
        return $this->responses->success($this->portal->addBalanceAdjustment((string) $request->route('uuid'), $request->body(), $this->admin($request), $this->ip($request), $this->requestId($request)), [], 'Balance adjustment recorded.', 201);
    }

    public function reportingSnapshot(Request $request): Response
    {
        return $this->responses->success($this->portal->addReportingSnapshot((string) $request->route('uuid'), $request->body(), $this->admin($request), $this->ip($request), $this->requestId($request)), [], 'Reporting snapshot published.', 201);
    }

    private function reason(Request $request): string
    {
        $reason = $request->input('reason');
        if (!is_string($reason) || trim($reason) === '') {
            throw new ValidationException(['reason' => ['A review reason is required.']]);
        }
        return trim($reason);
    }

    private function admin(Request $request): AdminUser
    {
        $admin = $request->attribute('admin_user');
        if (!$admin instanceof AdminUser) {
            throw new UnauthorizedException();
        }
        return $admin;
    }

    private function ip(Request $request): string
    {
        $value = $request->attribute('client_ip', 'unknown');
        return is_string($value) ? substr($value, 0, 45) : 'unknown';
    }

    private function requestId(Request $request): ?string
    {
        $value = $request->attribute('request_id');
        return is_string($value) ? $value : null;
    }
}
