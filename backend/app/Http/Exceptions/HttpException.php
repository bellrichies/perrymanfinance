<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $fields
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $fields = [],
        public readonly array $headers = [],
    ) { parent::__construct($message);
    }
}
