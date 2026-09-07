<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'NOT_FOUND', 'The requested resource was not found.');
    }
}
