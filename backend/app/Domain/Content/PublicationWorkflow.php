<?php

declare(strict_types=1);

namespace PerrymanFinance\Domain\Content;

use PerrymanFinance\Http\Exceptions\ValidationException;

final class PublicationWorkflow
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['draft', 'review'],
        'review' => ['review', 'draft', 'published'],
        'published' => ['published', 'draft', 'archived'],
        'archived' => ['archived', 'draft'],
    ];

    public function guard(string $current, string $next): void
    {
        if (!isset(self::TRANSITIONS[$current]) || !in_array($next, self::TRANSITIONS[$current], true)) {
            throw new ValidationException(['status' => ["Cannot transition from {$current} to {$next}."]]);
        }
    }
}
