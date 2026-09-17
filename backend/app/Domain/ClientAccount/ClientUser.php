<?php

declare(strict_types=1);

namespace PerrymanFinance\Domain\ClientAccount;

final readonly class ClientUser
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email,
        public string $passwordHash,
        public string $status,
        public ?string $emailVerifiedAt,
        public ?string $lastLoginAt,
        public ?string $firstName = null,
        public ?string $lastName = null,
    ) {
    }

    public function canLogin(): bool
    {
        return $this->status === 'active' && $this->emailVerifiedAt !== null;
    }

    /** @return array<string, mixed> */
    public function publicData(): array
    {
        return [
            'uuid' => $this->uuid,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => $this->emailVerifiedAt,
            'profile' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
            ],
        ];
    }
}
