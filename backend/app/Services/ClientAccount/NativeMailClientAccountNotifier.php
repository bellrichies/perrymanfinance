<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\ClientAccount;

final class NativeMailClientAccountNotifier implements ClientAccountNotifierInterface
{
    public function sendVerification(string $email, string $name, string $url): void
    {
        @mail($email, 'Verify your PerrymanFinance client account', "Hello {$name},\n\nVerify your account: {$url}");
    }

    public function sendPasswordReset(string $email, string $name, string $url): void
    {
        @mail($email, 'Reset your PerrymanFinance client password', "Hello {$name},\n\nReset your password: {$url}");
    }
}
