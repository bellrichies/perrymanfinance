<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Middleware;

use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\Exceptions\ForbiddenException;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;

final readonly class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private string $permission)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $user = $request->attribute('admin_user');
        if (!$user instanceof AdminUser) {
            throw new UnauthorizedException();
        }
        $resource = explode('.', $this->permission, 2)[0] . '.*';
        if (!in_array($this->permission, $user->permissions, true) && !in_array($resource, $user->permissions, true)) {
            throw new ForbiddenException();
        }
        return $next($request);
    }
}
