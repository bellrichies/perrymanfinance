<?php

declare(strict_types=1);

namespace Tests\Support;

use PerrymanFinance\Logging\LoggerInterface;

final class InMemoryLogger implements LoggerInterface
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */ public array $entries = [];
    public function log(string $level, string $message, array $context = []): void
    {
        $this->entries[] = compact('level', 'message', 'context');
    }
}
