<?php

declare(strict_types=1);

namespace Tests\Support;

use PerrymanFinance\Services\Identity\PasswordResetNotifierInterface;

final class CapturingResetNotifier implements PasswordResetNotifierInterface
{
    public ?string $url = null;

    public function send(string $email, string $displayName, string $resetUrl): void
    {
        $this->url = $resetUrl;
    }
}
