<?php

declare(strict_types=1);

namespace PerrymanFinance\Database\Seeders;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class ReferenceDataSeeder implements Seeder
{
    public function name(): string { return 'reference_roles_and_permissions'; }
    public function run(PDO $connection): void
    {
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        $roles = [['super_admin', 'Super administrator'], ['content_admin', 'Content administrator'], ['editor', 'Editor'], ['viewer', 'Viewer']];
        $permissions = [
            'pages.view', 'pages.create', 'pages.update', 'pages.publish', 'pages.archive',
            'investments.view', 'investments.create', 'investments.update', 'investments.publish',
            'investments.archive', 'articles.view', 'articles.create', 'articles.update',
            'articles.publish', 'articles.archive', 'faqs.manage', 'enquiries.view', 'enquiries.update',
            'media.manage', 'settings.manage', 'users.manage', 'audit.view', 'legal.manage',
        ];
        $roleStatement = $connection->prepare(
            'INSERT INTO roles (name, label, description, created_at, updated_at) '
            . 'VALUES (:name, :label, NULL, :created_at, :updated_at) '
            . 'ON DUPLICATE KEY UPDATE label = VALUES(label), updated_at = VALUES(updated_at)',
        );
        foreach ($roles as [$name, $label]) {
            $roleStatement->execute(['name' => $name, 'label' => $label, 'created_at' => $now, 'updated_at' => $now]);
        }
        $permissionStatement = $connection->prepare(
            'INSERT INTO permissions (name, label, created_at, updated_at) '
            . 'VALUES (:name, :label, :created_at, :updated_at) '
            . 'ON DUPLICATE KEY UPDATE label = VALUES(label), updated_at = VALUES(updated_at)',
        );
        foreach ($permissions as $permission) {
            $permissionStatement->execute([
                'name' => $permission, 'label' => ucwords(str_replace(['.', '_'], ' ', $permission)),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $assignments = [
            'super_admin' => $permissions,
            'content_admin' => array_values(array_filter($permissions, static fn (string $permission): bool => !in_array($permission, ['users.manage', 'audit.view'], true))),
            'editor' => array_values(array_filter($permissions, static fn (string $permission): bool => str_starts_with($permission, 'pages.') || str_starts_with($permission, 'articles.') || $permission === 'faqs.manage')),
            'viewer' => ['pages.view', 'investments.view', 'articles.view', 'enquiries.view'],
        ];
        $mappingStatement = $connection->prepare(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at) '
            . 'SELECT r.id, p.id, :created_at FROM roles r CROSS JOIN permissions p WHERE r.name = :role AND p.name = :permission',
        );
        foreach ($assignments as $role => $grants) {
            foreach ($grants as $permission) {
                $mappingStatement->execute(['created_at' => $now, 'role' => $role, 'permission' => $permission]);
            }
        }
    }
}
