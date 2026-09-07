<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\ForbiddenException;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Investment\InvestmentService;

final readonly class InvestmentController
{
    public function __construct(private InvestmentService $service, private ApiResponseFactory $responses)
    {
    }

    public function publicList(Request $request): Response
    {
        $result = $this->service->opportunities($request->query(), true);
        return $this->responses->success($result['items'], $result['meta']);
    }
    public function publicDetail(Request $request): Response
    {
        return $this->responses->success($this->service->opportunity((string) $request->route('slug'), true));
    }
    public function categories(Request $request): Response
    {
        return $this->responses->success($this->service->categories());
    }
    public function adminList(Request $request): Response
    {
        $result = $this->service->opportunities($request->query());
        return $this->responses->success($result['items'], $result['meta']);
    }
    public function adminDetail(Request $request): Response
    {
        return $this->responses->success($this->service->opportunity((string) $request->route('uuid')));
    }
    public function create(Request $request): Response
    {
        return $this->responses->success($this->service->saveOpportunity(null, $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Investment opportunity created.', 201);
    }
    public function update(Request $request): Response
    {
        $status = $request->input('status');
        if ($status === 'published') {
            $this->requirePermission($request, 'investments.publish');
        } elseif ($status === 'archived') {
            $this->requirePermission($request, 'investments.archive');
        }
        return $this->responses->success($this->service->saveOpportunity((string) $request->route('uuid'), $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Investment opportunity updated.');
    }
    public function archive(Request $request): Response
    {
        return $this->responses->success($this->service->archive((string) $request->route('uuid'), $this->actor($request)->id, $this->requestId($request)), [], 'Investment opportunity archived.');
    }
    public function createCategory(Request $request): Response
    {
        return $this->responses->success($this->service->saveCategory(null, $request->body()), [], 'Category created.', 201);
    }
    public function updateCategory(Request $request): Response
    {
        return $this->responses->success($this->service->saveCategory((int) $request->route('id'), $request->body()), [], 'Category updated.');
    }
    public function deleteCategory(Request $request): Response
    {
        $this->service->deleteCategory((int) $request->route('id'));
        return $this->responses->success(null, [], 'Category deleted.');
    }
    private function actor(Request $request): AdminUser
    {
        $user = $request->attribute('admin_user');
        return $user instanceof AdminUser ? $user : throw new UnauthorizedException();
    }
    private function requestId(Request $request): ?string
    {
        $id = $request->attribute('request_id');
        return is_string($id) ? $id : null;
    }
    private function requirePermission(Request $request, string $permission): void
    {
        $permissions = $this->actor($request)->permissions;
        if (!in_array($permission, $permissions, true) && !in_array('investments.*', $permissions, true)) {
            throw new ForbiddenException();
        }
    }
}
