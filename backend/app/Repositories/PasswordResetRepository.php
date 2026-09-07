<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class PasswordResetRepository extends AbstractRepository
{
    public function replaceForUser(int $userId, string $hash, string $expiresAt, string $now): void
    {
        $this->execute('UPDATE password_reset_tokens SET used_at = :now WHERE admin_user_id = :user AND used_at IS NULL', ['now' => $now, 'user' => $userId]);
        $this->execute(
            'INSERT INTO password_reset_tokens (admin_user_id, token_hash, expires_at, created_at) VALUES (:user, :hash, :expires, :now)',
            ['user' => $userId, 'hash' => $hash, 'expires' => $expiresAt, 'now' => $now],
        );
    }

    /** @return array<string, mixed>|null */
    public function findUsable(string $hash, string $now): ?array
    {
        $row = $this->execute(
            'SELECT * FROM password_reset_tokens WHERE token_hash = :hash AND used_at IS NULL AND expires_at > :now LIMIT 1',
            ['hash' => $hash, 'now' => $now],
        )->fetch();
        return is_array($row) ? $row : null;
    }

    public function markUsed(int $id, string $now): void
    {
        $this->execute('UPDATE password_reset_tokens SET used_at = :now WHERE id = :id AND used_at IS NULL', ['now' => $now, 'id' => $id]);
    }
}
