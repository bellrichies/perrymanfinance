<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Seeders;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class DevAdminSeeder implements Seeder
{
    private const EMAIL = 'admin@example.test';
    private const PASSWORD = 'Correct-password-123';

    public function name(): string
    {
        return 'development_admin_user';
    }

    public function run(PDO $connection): void
    {
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');

        $statement = $connection->prepare(
            'INSERT INTO admin_users (uuid,email,password_hash,display_name,status,created_at,updated_at)
            VALUES (:uuid,:email,:password,:name,:status,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE
                password_hash=VALUES(password_hash),
                display_name=VALUES(display_name),
                status=VALUES(status),
                updated_at=VALUES(updated_at),
                deleted_at=NULL',
        );
        $statement->execute([
            'uuid' => $this->uuid('dev-admin'),
            'email' => self::EMAIL,
            'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'name' => 'Development Admin',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $assignRole = $connection->prepare(
            'INSERT IGNORE INTO user_roles (admin_user_id, role_id, created_at)
            SELECT u.id, r.id, :created_at
            FROM admin_users u
            JOIN roles r ON r.name = :role
            WHERE u.email = :email',
        );
        $assignRole->execute([
            'created_at' => $now,
            'role' => 'super_admin',
            'email' => self::EMAIL,
        ]);
    }

    private function uuid(string $key): string
    {
        $hash = md5('perrymanfinance-' . $key);
        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-4' . substr($hash, 13, 3) . '-a' . substr($hash, 17, 3) . '-' . substr($hash, 20, 12);
    }
}
