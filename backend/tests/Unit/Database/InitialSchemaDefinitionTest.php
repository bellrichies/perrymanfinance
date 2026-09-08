<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PerrymanFinance\Database\Migrations\MigrationRegistry;
use PHPUnit\Framework\TestCase;

final class InitialSchemaDefinitionTest extends TestCase
{
    public function testAllRequiredTablesAreDefinedWithMySqlStorageRequirements(): void
    {
        $directory = dirname(__DIR__, 3) . '/database/migrations';
        self::assertCount(8, (new MigrationRegistry($directory))->all());
        $contents = '';
        foreach (glob($directory . '/*.php') ?: [] as $file) {
            $contents .= (string) file_get_contents($file);
        }
        $tables = [
            'admin_users', 'roles', 'permissions', 'role_permissions', 'user_roles',
            'password_reset_tokens', 'refresh_tokens', 'audit_logs', 'pages', 'page_sections',
            'legal_documents', 'media_assets', 'site_settings', 'investment_categories',
            'investment_opportunities', 'article_categories', 'articles', 'tags', 'article_tags',
            'faqs', 'enquiries', 'seo_metadata', 'redirects', 'rate_limit_counters',
        ];
        foreach ($tables as $table) {
            self::assertStringContainsString("CREATE TABLE {$table}", $contents);
        }
        self::assertSame(24, substr_count($contents, 'ENGINE=InnoDB'));
        self::assertSame(24, substr_count($contents, 'DEFAULT CHARSET=utf8mb4'));
        self::assertStringContainsString('DATETIME(6)', $contents);
        self::assertStringContainsString('FOREIGN KEY', $contents);
        self::assertStringContainsString('UNIQUE KEY', $contents);
    }
}
