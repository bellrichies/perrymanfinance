<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Domain\ClientAccount\ClientUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\ClientAccount\ClientPortalService;

final readonly class ClientPortalController
{
    public function __construct(private ClientPortalService $portal, private ApiResponseFactory $responses)
    {
    }

    public function dashboard(Request $request): Response
    {
        return $this->responses->success($this->portal->dashboard($this->client($request)))->withHeader('Cache-Control', 'no-store, private');
    }

    public function plans(): Response
    {
        return $this->responses->success($this->portal->eligiblePlans())->withHeader('Cache-Control', 'no-store, private');
    }

    public function submitPlanRequest(Request $request): Response
    {
        return $this->responses->success($this->portal->submitPlanRequest($this->client($request), $request->body(), $this->ip($request), $this->requestId($request)), [], 'Plan request submitted for review.', 201)->withHeader('Cache-Control', 'no-store, private');
    }

    public function planRequests(Request $request): Response
    {
        return $this->responses->success($this->portal->dashboard($this->client($request))['plan_requests'])->withHeader('Cache-Control', 'no-store, private');
    }

    public function snapshots(Request $request): Response
    {
        return $this->responses->success($this->portal->investmentSnapshots($this->client($request), (string) $request->route('uuid')))->withHeader('Cache-Control', 'no-store, private');
    }

    private function client(Request $request): ClientUser
    {
        $client = $request->attribute('client_user');
        if (!$client instanceof ClientUser) {
            throw new UnauthorizedException();
        }
        return $client;
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
