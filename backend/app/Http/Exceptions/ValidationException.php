<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

final class ValidationException extends HttpException
{
    /** @param array<string, list<string>> $fields */
    public function __construct(array $fields)
    {
        parent::__construct(422, 'VALIDATION_FAILED', 'The submitted data is invalid.', $fields);
    }
}
