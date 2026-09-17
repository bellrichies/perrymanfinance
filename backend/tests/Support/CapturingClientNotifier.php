<?php

declare(strict_types=1);

namespace Tests\Support;

use PerrymanFinance\Services\ClientAccount\ClientAccountNotifierInterface;

final class CapturingClientNotifier implements ClientAccountNotifierInterface
{
    public ?string $verificationUrl = null;
    public ?string $resetUrl = null;

    public function sendVerification(string $email, string $name, string $url): void
    {
        $this->verificationUrl = $url;
    }

    public function sendPasswordReset(string $email, string $name, string $url): void
    {
        $this->resetUrl = $url;
    }
}
