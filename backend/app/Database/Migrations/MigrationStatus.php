<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Migrations;

final readonly class MigrationStatus
{
    public function __construct(public string $name, public bool $applied, public ?int $batch)
    {
    }
}
