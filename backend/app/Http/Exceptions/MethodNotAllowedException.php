<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class MethodNotAllowedException extends HttpException
{
    /** @param list<string> $allowed */
    public function __construct(array $allowed)
    {
        sort($allowed);
        parent::__construct(
            405,
            'METHOD_NOT_ALLOWED',
            'The request method is not allowed for this resource.',
            [],
            ['Allow' => implode(', ', $allowed)],
        );
    }
}
