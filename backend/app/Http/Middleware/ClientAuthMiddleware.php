<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\ClientUserRepository;
use PerrymanFinance\Services\Identity\TokenService;

final readonly class ClientAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private TokenService $tokens, private ClientUserRepository $users)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $authorization = $request->header('Authorization', '');
        if (!is_string($authorization) || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new UnauthorizedException();
        }
        $claims = $this->tokens->verifyAccessToken($matches[1]);
        if (!in_array('client', is_array($claims['permissions'] ?? null) ? $claims['permissions'] : [], true)) {
            throw new UnauthorizedException('The access token is invalid or expired.');
        }
        $user = $this->users->findById((int) $claims['sub']);
        if ($user === null || !$user->canLogin()) {
            throw new UnauthorizedException('The access token is invalid or expired.');
        }
        $request->setAttribute('client_user', $user);
        return $next($request);
    }
}
