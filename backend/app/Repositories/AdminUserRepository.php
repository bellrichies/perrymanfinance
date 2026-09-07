<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

use PerrymanFinance\Domain\Identity\AdminUser;

final class AdminUserRepository extends AbstractRepository
{
    public function findByEmail(string $email): ?AdminUser
    {
        $row = $this->execute(
            'SELECT id, uuid, email, password_hash, display_name, status FROM admin_users '
            . 'WHERE email = :email AND deleted_at IS NULL LIMIT 1',
            ['email' => mb_strtolower(trim($email))],
        )->fetch();
        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?AdminUser
    {
        $row = $this->execute(
            'SELECT id, uuid, email, password_hash, display_name, status FROM admin_users '
            . 'WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id],
        )->fetch();
        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function recordLogin(int $id, string $now): void
    {
        $this->execute('UPDATE admin_users SET last_login_at = :now, updated_at = :now WHERE id = :id', ['now' => $now, 'id' => $id]);
    }

    public function updatePassword(int $id, string $hash, string $now): void
    {
        $this->execute(
            'UPDATE admin_users SET password_hash = :hash, updated_at = :now WHERE id = :id',
            ['hash' => $hash, 'now' => $now, 'id' => $id],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): AdminUser
    {
        $roles = $this->execute(
            'SELECT r.name FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id '
            . 'WHERE ur.admin_user_id = :id ORDER BY r.name',
            ['id' => $row['id']],
        )->fetchAll(\PDO::FETCH_COLUMN);
        $permissions = $this->execute(
            'SELECT DISTINCT p.name FROM permissions p INNER JOIN role_permissions rp ON rp.permission_id = p.id '
            . 'INNER JOIN user_roles ur ON ur.role_id = rp.role_id WHERE ur.admin_user_id = :id ORDER BY p.name',
            ['id' => $row['id']],
        )->fetchAll(\PDO::FETCH_COLUMN);
        return new AdminUser(
            (int) $row['id'],
            (string) $row['uuid'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['display_name'],
            (string) $row['status'],
            array_values($roles),
            array_values($permissions),
        );
    }
}
