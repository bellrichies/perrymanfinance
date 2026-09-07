<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class RefreshTokenRepository extends AbstractRepository
{
    public function create(int $userId, string $hash, string $expiresAt, string $now): int
    {
        $this->execute(
            'INSERT INTO refresh_tokens (admin_user_id, token_hash, expires_at, created_at) VALUES (:user, :hash, :expires, :now)',
            ['user' => $userId, 'hash' => $hash, 'expires' => $expiresAt, 'now' => $now],
        );
        return (int) $this->connection()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(string $hash): ?array
    {
        $row = $this->execute('SELECT * FROM refresh_tokens WHERE token_hash = :hash LIMIT 1', ['hash' => $hash])->fetch();
        return is_array($row) ? $row : null;
    }

    public function rotate(int $oldId, int $newId, string $now): bool
    {
        return $this->execute(
            'UPDATE refresh_tokens SET revoked_at = :now, replaced_by_id = :replacement WHERE id = :id AND revoked_at IS NULL',
            ['now' => $now, 'replacement' => $newId, 'id' => $oldId],
        )->rowCount() === 1;
    }

    public function revokeByHash(string $hash, string $now): void
    {
        $this->execute('UPDATE refresh_tokens SET revoked_at = COALESCE(revoked_at, :now) WHERE token_hash = :hash', ['now' => $now, 'hash' => $hash]);
    }

    public function revokeAllForUser(int $userId, string $now): void
    {
        $this->execute('UPDATE refresh_tokens SET revoked_at = COALESCE(revoked_at, :now) WHERE admin_user_id = :user', ['now' => $now, 'user' => $userId]);
    }
}
