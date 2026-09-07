<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Identity;

use PerrymanFinance\Config\Config;

final readonly class NativeMailPasswordResetNotifier implements PasswordResetNotifierInterface
{
    public function __construct(private Config $config)
    {
    }

    public function send(string $email, string $displayName, string $resetUrl): void
    {
        $from = $this->config->string('mail.from');
        if ($from === '') {
            throw new \RuntimeException('MAIL_FROM_ADDRESS is not configured.');
        }
        $subject = 'Reset your PerrymanFinance admin password';
        $body = "Hello {$displayName},\n\nUse this one-time link to reset your admin password:\n{$resetUrl}\n\nIf you did not request this, ignore this email.";
        if (!mail($email, $subject, $body, "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8")) {
            throw new \RuntimeException('Password reset email could not be sent.');
        }
    }
}
