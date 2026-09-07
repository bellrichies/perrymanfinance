<?php

declare(strict_types=1);

use PerrymanFinance\Database\Migrations\Migration;

return new class implements Migration {
    public function name(): string { return '202609070005_create_enquiry_tables'; }
    public function up(PDO $connection): void
    {
        $connection->exec("CREATE TABLE enquiries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uuid CHAR(36) NOT NULL,
            name VARCHAR(160) NOT NULL, email VARCHAR(254) NOT NULL, phone VARCHAR(40) NULL,
            enquiry_type VARCHAR(80) NOT NULL, subject VARCHAR(255) NOT NULL, message TEXT NOT NULL,
            source_page VARCHAR(500) NULL, consent_at DATETIME(6) NOT NULL,
            status ENUM('new','in_progress','resolved','spam','closed') NOT NULL DEFAULT 'new',
            assigned_to BIGINT UNSIGNED NULL, resolved_at DATETIME(6) NULL,
            created_at DATETIME(6) NOT NULL, updated_at DATETIME(6) NOT NULL, deleted_at DATETIME(6) NULL,
            UNIQUE KEY uq_enquiries_uuid (uuid), KEY idx_enquiries_status_created (status, created_at),
            KEY idx_enquiries_email_created (email, created_at), KEY idx_enquiries_assignee (assigned_to),
            CONSTRAINT fk_enquiries_assignee FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    public function down(PDO $connection): void { $connection->exec('DROP TABLE IF EXISTS enquiries'); }
};
