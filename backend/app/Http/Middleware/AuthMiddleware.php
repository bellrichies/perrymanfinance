<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\AdminUserRepository;
use PerrymanFinance\Services\Identity\TokenService;

final readonly class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private TokenService $tokens, private AdminUserRepository $users)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $authorization = $request->header('Authorization', '');
        if (!is_string($authorization) || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new UnauthorizedException();
        }
        $claims = $this->tokens->verifyAccessToken($matches[1]);
        $user = $this->users->findById((int) $claims['sub']);
        if ($user === null || !$user->isActive()) {
            throw new UnauthorizedException('The access token is invalid or expired.');
        }
        $request->setAttribute('admin_user', $user);
        return $next($request);
    }
}
