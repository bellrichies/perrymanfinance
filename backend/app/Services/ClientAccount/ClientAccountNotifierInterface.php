<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\ClientAccount;

interface ClientAccountNotifierInterface
{
    public function sendVerification(string $email, string $name, string $url): void;

    public function sendPasswordReset(string $email, string $name, string $url): void;
}
