<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class BadRequestException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(400, 'BAD_REQUEST', $message);
    }
}
