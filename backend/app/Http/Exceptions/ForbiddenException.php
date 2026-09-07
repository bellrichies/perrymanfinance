<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class ForbiddenException extends HttpException
{
    public function __construct()
    {
        parent::__construct(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
    }
}
