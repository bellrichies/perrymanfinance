<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Enquiries\AdminEnquiryService;

final readonly class AdminEnquiryController
{
    public function __construct(private AdminEnquiryService $service, private ApiResponseFactory $responses)
    {
    }

    public function index(Request $request): Response
    {
        $result = $this->service->listing($request->query());
        return $this->responses->success($result['items'], $result['meta']);
    }

    public function show(Request $request): Response
    {
        return $this->responses->success($this->service->detail((string) $request->route('uuid')));
    }

    public function update(Request $request): Response
    {
        $actor = $request->attribute('admin_user');
        if (!$actor instanceof AdminUser) {
            throw new UnauthorizedException();
        }
        $requestId = $request->attribute('request_id');
        return $this->responses->success($this->service->update((string) $request->route('uuid'), $request->body(), $actor->id, is_string($requestId) ? $requestId : null), [], 'Enquiry status updated.');
    }
}
