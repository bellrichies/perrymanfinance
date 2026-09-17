<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\AdminUserRepository;
use PerrymanFinance\Repositories\AuditLogRepository;

final readonly class AdminOperationsController
{
    public function __construct(
        private AdminUserRepository $users,
        private AuditLogRepository $audit,
        private ApiResponseFactory $responses,
    ) {
    }

    public function users(): Response
    {
        return $this->responses->success($this->users->list());
    }

    public function auditLogs(Request $request): Response
    {
        $limit = $request->input('limit', 100);
        return $this->responses->success($this->audit->latest(is_numeric($limit) ? (int) $limit : 100));
    }
}
