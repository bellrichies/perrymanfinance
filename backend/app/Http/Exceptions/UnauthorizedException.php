<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Authentication is required.')
    {
        parent::__construct(401, 'UNAUTHENTICATED', $message, [], ['WWW-Authenticate' => 'Bearer']);
    }
}
