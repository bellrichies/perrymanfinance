<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class ClientTokenRepository extends AbstractRepository
{
    public function createSession(int $clientUserId, string $hash, string $expiresAt, string $now): int
    {
        $this->execute('INSERT INTO client_sessions (client_user_id,token_hash,expires_at,created_at) VALUES (:user,:hash,:expires,:now)', ['user' => $clientUserId, 'hash' => $hash, 'expires' => $expiresAt, 'now' => $now]);
        return (int) $this->connection()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findSession(string $hash): ?array
    {
        $row = $this->execute('SELECT * FROM client_sessions WHERE token_hash=:hash LIMIT 1', ['hash' => $hash])->fetch();
        return is_array($row) ? $row : null;
    }

    public function rotateSession(int $oldId, int $newId, string $now): bool
    {
        $this->execute('UPDATE client_sessions SET revoked_at=:now,replaced_by_id=:new WHERE id=:old AND revoked_at IS NULL', ['old' => $oldId, 'new' => $newId, 'now' => $now]);
        return true;
    }

    public function revokeSession(string $hash, string $now): void
    {
        $this->execute('UPDATE client_sessions SET revoked_at=:now WHERE token_hash=:hash AND revoked_at IS NULL', ['hash' => $hash, 'now' => $now]);
    }

    public function revokeAllSessions(int $clientUserId, string $now): void
    {
        $this->execute('UPDATE client_sessions SET revoked_at=:now WHERE client_user_id=:id AND revoked_at IS NULL', ['id' => $clientUserId, 'now' => $now]);
    }

    public function replaceVerificationToken(int $clientUserId, string $hash, string $expiresAt, string $now): void
    {
        $this->execute('UPDATE client_email_verification_tokens SET used_at=:now WHERE client_user_id=:id AND used_at IS NULL', ['id' => $clientUserId, 'now' => $now]);
        $this->execute('INSERT INTO client_email_verification_tokens (client_user_id,token_hash,expires_at,created_at) VALUES (:id,:hash,:expires,:now)', ['id' => $clientUserId, 'hash' => $hash, 'expires' => $expiresAt, 'now' => $now]);
    }

    /** @return array<string, mixed>|null */
    public function findVerificationToken(string $hash, string $now): ?array
    {
        $row = $this->execute('SELECT * FROM client_email_verification_tokens WHERE token_hash=:hash AND used_at IS NULL AND expires_at>:now LIMIT 1', ['hash' => $hash, 'now' => $now])->fetch();
        return is_array($row) ? $row : null;
    }

    public function markVerificationUsed(int $id, string $now): void
    {
        $this->execute('UPDATE client_email_verification_tokens SET used_at=:now WHERE id=:id', ['id' => $id, 'now' => $now]);
    }

    public function replaceResetToken(int $clientUserId, string $hash, string $expiresAt, string $now): void
    {
        $this->execute('UPDATE client_password_reset_tokens SET used_at=:now WHERE client_user_id=:id AND used_at IS NULL', ['id' => $clientUserId, 'now' => $now]);
        $this->execute('INSERT INTO client_password_reset_tokens (client_user_id,token_hash,expires_at,created_at) VALUES (:id,:hash,:expires,:now)', ['id' => $clientUserId, 'hash' => $hash, 'expires' => $expiresAt, 'now' => $now]);
    }

    /** @return array<string, mixed>|null */
    public function findResetToken(string $hash, string $now): ?array
    {
        $row = $this->execute('SELECT * FROM client_password_reset_tokens WHERE token_hash=:hash AND used_at IS NULL AND expires_at>:now LIMIT 1', ['hash' => $hash, 'now' => $now])->fetch();
        return is_array($row) ? $row : null;
    }

    public function markResetUsed(int $id, string $now): void
    {
        $this->execute('UPDATE client_password_reset_tokens SET used_at=:now WHERE id=:id', ['id' => $id, 'now' => $now]);
    }
}
