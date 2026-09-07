<?php

declare(strict_types=1);

namespace PerrymanFinance\Domain\Identity;

final readonly class AdminUser
{
    /**
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email,
        public string $passwordHash,
        public string $displayName,
        public string $status,
        public array $roles,
        public array $permissions,
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** @return array<string, mixed> */
    public function publicData(): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'display_name' => $this->displayName,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];
    }
}
