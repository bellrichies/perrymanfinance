<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class TooManyRequestsException extends HttpException
{
    public function __construct(int $retryAfter)
    {
        parent::__construct(429, 'RATE_LIMITED', 'Too many attempts. Please try again later.', [], ['Retry-After' => (string) $retryAfter]);
    }
}
