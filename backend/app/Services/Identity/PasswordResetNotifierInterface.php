<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Identity;

interface PasswordResetNotifierInterface
{
    public function send(string $email, string $displayName, string $resetUrl): void;
}
