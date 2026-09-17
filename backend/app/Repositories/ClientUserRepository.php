<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

use PerrymanFinance\Domain\ClientAccount\ClientUser;

final class ClientUserRepository extends AbstractRepository
{
    public function findByEmail(string $email): ?ClientUser
    {
        return $this->map($this->execute(
            'SELECT u.*,p.first_name,p.last_name FROM client_users u LEFT JOIN client_profiles p ON p.client_user_id=u.id WHERE u.email=:email LIMIT 1',
            ['email' => strtolower($email)],
        )->fetch() ?: null);
    }

    public function findById(int $id): ?ClientUser
    {
        return $this->map($this->execute(
            'SELECT u.*,p.first_name,p.last_name FROM client_users u LEFT JOIN client_profiles p ON p.client_user_id=u.id WHERE u.id=:id LIMIT 1',
            ['id' => $id],
        )->fetch() ?: null);
    }

    /** @return array<string, mixed>|null */
    public function findByUuid(string $uuid): ?array
    {
        $row = $this->execute(
            'SELECT u.uuid,u.email,u.status,u.email_verified_at,u.last_login_at,u.created_at,p.first_name,p.last_name,p.phone,p.country FROM client_users u LEFT JOIN client_profiles p ON p.client_user_id=u.id WHERE u.uuid=:uuid LIMIT 1',
            ['uuid' => $uuid],
        )->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $profile */
    public function create(string $uuid, string $email, string $passwordHash, array $profile, string $now): int
    {
        $this->execute(
            "INSERT INTO client_users (uuid,email,password_hash,status,created_at,updated_at) VALUES (:uuid,:email,:hash,'pending_verification',:created_at,:updated_at)",
            ['uuid' => $uuid, 'email' => strtolower($email), 'hash' => $passwordHash, 'created_at' => $now, 'updated_at' => $now],
        );
        $id = (int) $this->connection()->lastInsertId();
        $this->execute(
            'INSERT INTO client_profiles (client_user_id,first_name,last_name,phone,country,consent_marketing,consent_terms_at,created_at,updated_at) VALUES (:id,:first,:last,:phone,:country,:marketing,:terms_at,:created_at,:updated_at)',
            ['id' => $id, 'first' => $profile['first_name'], 'last' => $profile['last_name'], 'phone' => $profile['phone'] ?? null, 'country' => $profile['country'] ?? null, 'marketing' => !empty($profile['consent_marketing']) ? 1 : 0, 'terms_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        );
        return $id;
    }

    public function verifyEmail(int $id, string $now): void
    {
        $this->execute("UPDATE client_users SET status='active',email_verified_at=:verified_at,updated_at=:updated_at WHERE id=:id AND email_verified_at IS NULL", ['id' => $id, 'verified_at' => $now, 'updated_at' => $now]);
    }

    public function recordLogin(int $id, string $now): void
    {
        $this->execute('UPDATE client_users SET last_login_at=:last_login_at,updated_at=:updated_at WHERE id=:id', ['id' => $id, 'last_login_at' => $now, 'updated_at' => $now]);
    }

    public function updatePassword(int $id, string $hash, string $now): void
    {
        $this->execute('UPDATE client_users SET password_hash=:hash,updated_at=:now WHERE id=:id', ['id' => $id, 'hash' => $hash, 'now' => $now]);
    }

    /** @return list<array<string, mixed>> */
    public function list(string $search = ''): array
    {
        $params = [];
        $where = '1=1';
        if ($search !== '') {
            $where = '(u.email LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        return array_values($this->execute(
            "SELECT u.uuid,u.email,u.status,u.email_verified_at,u.last_login_at,u.created_at,p.first_name,p.last_name FROM client_users u LEFT JOIN client_profiles p ON p.client_user_id=u.id WHERE {$where} ORDER BY u.created_at DESC LIMIT 100",
            $params,
        )->fetchAll());
    }

    /** @param mixed $row */
    private function map(mixed $row): ?ClientUser
    {
        if (!is_array($row)) {
            return null;
        }
        return new ClientUser((int) $row['id'], (string) $row['uuid'], (string) $row['email'], (string) $row['password_hash'], (string) $row['status'], $row['email_verified_at'] !== null ? (string) $row['email_verified_at'] : null, $row['last_login_at'] !== null ? (string) $row['last_login_at'] : null, $row['first_name'] !== null ? (string) $row['first_name'] : null, $row['last_name'] !== null ? (string) $row['last_name'] : null);
    }
}
