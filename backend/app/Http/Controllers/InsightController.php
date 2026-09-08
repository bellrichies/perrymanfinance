<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\ForbiddenException;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Insights\InsightService;

final readonly class InsightController
{
    public function __construct(private InsightService $service, private ApiResponseFactory $responses)
    {
    }

    public function publicList(Request $request): Response
    {
        $result = $this->service->articles($request->query(), true);
        return $this->responses->success($result['items'], $result['meta']);
    }

    public function publicDetail(Request $request): Response
    {
        return $this->responses->success($this->service->article((string) $request->route('slug'), true));
    }

    public function publicFaq(Request $request): Response
    {
        $category = is_string($request->query()['category'] ?? null) ? (string) $request->query()['category'] : null;
        return $this->responses->success($this->service->faqs(true, $category));
    }

    public function categories(Request $request): Response
    {
        return $this->responses->success($this->service->categories());
    }

    public function tags(Request $request): Response
    {
        return $this->responses->success($this->service->tags());
    }

    public function adminList(Request $request): Response
    {
        $result = $this->service->articles($request->query());
        return $this->responses->success($result['items'], $result['meta']);
    }

    public function adminDetail(Request $request): Response
    {
        return $this->responses->success($this->service->article((string) $request->route('uuid')));
    }

    public function create(Request $request): Response
    {
        return $this->responses->success($this->service->saveArticle(null, $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Article created.', 201);
    }

    public function update(Request $request): Response
    {
        $status = $request->input('status');
        if ($status === 'published') {
            $this->requirePermission($request, 'articles.publish');
        } elseif ($status === 'archived') {
            $this->requirePermission($request, 'articles.archive');
        }
        return $this->responses->success($this->service->saveArticle((string) $request->route('uuid'), $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Article updated.');
    }

    public function archive(Request $request): Response
    {
        return $this->responses->success($this->service->archive((string) $request->route('uuid'), $this->actor($request)->id, $this->requestId($request)), [], 'Article archived.');
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

    public function createTag(Request $request): Response
    {
        return $this->responses->success($this->service->saveTag(null, $request->body()), [], 'Tag created.', 201);
    }

    public function updateTag(Request $request): Response
    {
        return $this->responses->success($this->service->saveTag((int) $request->route('id'), $request->body()), [], 'Tag updated.');
    }

    public function deleteTag(Request $request): Response
    {
        $this->service->deleteTag((int) $request->route('id'));
        return $this->responses->success(null, [], 'Tag deleted.');
    }

    public function adminFaqs(Request $request): Response
    {
        return $this->responses->success($this->service->faqs());
    }

    public function createFaq(Request $request): Response
    {
        return $this->responses->success($this->service->saveFaq(null, $request->body(), $this->actor($request)->id), [], 'FAQ created.', 201);
    }

    public function updateFaq(Request $request): Response
    {
        return $this->responses->success($this->service->saveFaq((int) $request->route('id'), $request->body(), $this->actor($request)->id), [], 'FAQ updated.');
    }

    public function deleteFaq(Request $request): Response
    {
        $this->service->deleteFaq((int) $request->route('id'));
        return $this->responses->success(null, [], 'FAQ archived.');
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
        if (!in_array($permission, $permissions, true) && !in_array('articles.*', $permissions, true)) {
            throw new ForbiddenException();
        }
    }
}
