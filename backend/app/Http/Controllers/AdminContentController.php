<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Exceptions\ForbiddenException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Content\CmsService;
use PerrymanFinance\Services\Content\MediaService;

final readonly class AdminContentController
{
    public function __construct(
        private CmsService $cms,
        private MediaService $media,
        private ApiResponseFactory $responses,
    ) {
    }

    public function pages(Request $request): Response
    {
        return $this->responses->success($this->cms->pages());
    }

    public function page(Request $request): Response
    {
        return $this->responses->success($this->cms->page((string) $request->route('uuid')));
    }

    public function createPage(Request $request): Response
    {
        return $this->responses->success($this->cms->savePage(null, $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Page created.', 201);
    }

    public function updatePage(Request $request): Response
    {
        $status = $request->input('status');
        if ($status === 'published') {
            $this->requirePermission($request, 'pages.publish');
        } elseif ($status === 'archived') {
            $this->requirePermission($request, 'pages.archive');
        }
        return $this->responses->success($this->cms->savePage((string) $request->route('uuid'), $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Page updated.');
    }

    public function deletePage(Request $request): Response
    {
        $this->cms->deletePage((string) $request->route('uuid'), $this->actor($request)->id, $this->requestId($request));
        return $this->responses->success(null, [], 'Page deleted.');
    }

    public function legalDocuments(Request $request): Response
    {
        return $this->responses->success($this->cms->legalDocuments());
    }

    public function legal(Request $request): Response
    {
        return $this->responses->success($this->cms->legal((string) $request->route('uuid')));
    }

    public function createLegal(Request $request): Response
    {
        return $this->responses->success($this->cms->saveLegal(null, $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Legal document created.', 201);
    }

    public function updateLegal(Request $request): Response
    {
        return $this->responses->success($this->cms->saveLegal((string) $request->route('uuid'), $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'Legal document updated.');
    }

    public function archiveLegal(Request $request): Response
    {
        return $this->responses->success($this->cms->archiveLegal((string) $request->route('uuid'), $this->actor($request)->id, $this->requestId($request)), [], 'Legal document archived.');
    }

    public function settings(Request $request): Response
    {
        return $this->responses->success($this->cms->settings());
    }

    public function saveSetting(Request $request): Response
    {
        $this->cms->saveSetting([...$request->body(), 'key' => (string) $request->route('key')], $this->actor($request)->id, $this->requestId($request));
        return $this->responses->success(null, [], 'Setting saved.');
    }

    public function deleteSetting(Request $request): Response
    {
        $this->cms->deleteSetting((string) $request->route('key'), $this->actor($request)->id, $this->requestId($request));
        return $this->responses->success(null, [], 'Setting deleted.');
    }

    public function media(Request $request): Response
    {
        return $this->responses->success($this->media->all());
    }

    public function upload(Request $request): Response
    {
        $file = $request->files()['file'] ?? null;
        if (!is_array($file)) {
            throw new ValidationException(['file' => ['Choose an image to upload.']]);
        }
        $alt = $request->input('alt_text');
        return $this->responses->success($this->media->upload($file, is_string($alt) ? $alt : null, $this->actor($request)->id, $this->requestId($request)), [], 'Media uploaded.', 201);
    }

    public function deleteMedia(Request $request): Response
    {
        $this->media->delete((string) $request->route('uuid'), $this->actor($request)->id, $this->requestId($request));
        return $this->responses->success(null, [], 'Media deleted.');
    }

    public function updateMedia(Request $request): Response
    {
        $alt = $request->input('alt_text');
        return $this->responses->success($this->media->update((string) $request->route('uuid'), is_string($alt) ? $alt : null, $this->actor($request)->id, $this->requestId($request)), [], 'Media updated.');
    }

    public function seo(Request $request): Response
    {
        $type = $this->ownerType($request);
        $this->requirePermission($request, $type === 'page' ? 'pages.view' : 'legal.manage');
        return $this->responses->success($this->cms->seo($type, (string) $request->route('uuid')));
    }

    public function saveSeo(Request $request): Response
    {
        $type = $this->ownerType($request);
        $this->requirePermission($request, $type === 'page' ? 'pages.update' : 'legal.manage');
        return $this->responses->success($this->cms->saveSeo($type, (string) $request->route('uuid'), $request->body(), $this->actor($request)->id, $this->requestId($request)), [], 'SEO metadata saved.');
    }

    public function deleteSeo(Request $request): Response
    {
        $type = $this->ownerType($request);
        $this->requirePermission($request, $type === 'page' ? 'pages.update' : 'legal.manage');
        $this->cms->deleteSeo($type, (string) $request->route('uuid'), $this->actor($request)->id, $this->requestId($request));
        return $this->responses->success(null, [], 'SEO metadata deleted.');
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

    private function ownerType(Request $request): string
    {
        $type = (string) $request->route('type');
        if (!in_array($type, ['page', 'legal_document'], true)) {
            throw new ValidationException(['type' => ['SEO metadata supports pages and legal documents.']]);
        }
        return $type;
    }

    private function requirePermission(Request $request, string $permission): void
    {
        $permissions = $this->actor($request)->permissions;
        $wildcard = explode('.', $permission, 2)[0] . '.*';
        if (!in_array($permission, $permissions, true) && !in_array($wildcard, $permissions, true)) {
            throw new ForbiddenException();
        }
    }
}
