<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\EnquiryRepository;
use PerrymanFinance\Services\Enquiries\AdminEnquiryService;
use PHPUnit\Framework\TestCase;
use Tests\Support\SqliteConnection;

final class AdminEnquiryTest extends TestCase
{
    private PDO $pdo;
    private AdminEnquiryService $service;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->pdo->exec('CREATE TABLE enquiries (id INTEGER PRIMARY KEY,uuid TEXT,name TEXT,email TEXT,subject TEXT,enquiry_type TEXT,status TEXT,resolved_at TEXT,created_at TEXT,updated_at TEXT,deleted_at TEXT)');
        $this->pdo->exec('CREATE TABLE audit_logs (actor_id INTEGER,event TEXT,subject_type TEXT,subject_id TEXT,request_id TEXT,ip_address TEXT,after_json TEXT,created_at TEXT)');
        $this->pdo->exec("INSERT INTO enquiries VALUES (1,'test-uuid','Visitor','visitor@example.test','Consultation','consultation','new',NULL,'2026-09-08','2026-09-08',NULL)");
        $this->service = new AdminEnquiryService(new EnquiryRepository($connection), new TransactionManager($connection), new AuditLogRepository($connection));
    }

    public function testStatusWorkflowRecordsAuditWithoutPersonalData(): void
    {
        foreach (['in_progress', 'resolved', 'closed', 'in_progress', 'spam'] as $status) {
            $row = $this->service->update('test-uuid', ['status' => $status], 7, 'request-1');
            self::assertSame($status, $row['status']);
            self::assertSame($status !== 'resolved', $row['resolved_at'] === null);
        }
        $statement = $this->pdo->query('SELECT * FROM audit_logs');
        self::assertInstanceOf(\PDOStatement::class, $statement);
        $rows = $statement->fetchAll();
        self::assertCount(5, $rows);
        self::assertSame(7, $rows[0]['actor_id']);
        self::assertStringNotContainsString('visitor@example.test', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    public function testInvalidTransitionAndExtraFieldsAreRejected(): void
    {
        foreach ([['status' => 'resolved'], ['status' => 'invented'], ['status' => 'in_progress', 'notes' => 'not supported']] as $input) {
            try {
                $this->service->update('test-uuid', $input, 7, null);
                self::fail('Invalid update accepted.');
            } catch (ValidationException) {
                self::assertSame('new', $this->service->detail('test-uuid')['status']);
            }
        }
    }

    public function testAuditFailureRollsBackStatus(): void
    {
        $this->pdo->exec('DROP TABLE audit_logs');
        try {
            $this->service->update('test-uuid', ['status' => 'in_progress'], 7, null);
            self::fail('Audit failure ignored.');
        } catch (\PDOException) {
            self::assertSame('new', $this->service->detail('test-uuid')['status']);
        }
    }

    public function testSearchFilterAndPagination(): void
    {
        self::assertSame(1, $this->service->listing(['search' => 'Visitor', 'status' => 'new'])['meta']['total']);
        self::assertSame(0, $this->service->listing(['search' => "' OR 1=1 --"])['meta']['total']);
        self::assertSame([], $this->service->listing(['page' => 2])['items']);
        self::assertSame(50, $this->service->listing(['per_page' => 1000])['meta']['per_page']);
    }

    public function testActualAdminRoutesRejectAnonymousAccess(): void
    {
        $previous = $_ENV['JWT_SECRET'] ?? null;
        $_ENV['JWT_SECRET'] = bin2hex(random_bytes(32));
        try {
            $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
            foreach ([['GET', '/api/v1/admin/enquiries'], ['GET', '/api/v1/admin/enquiries/test-uuid'], ['PATCH', '/api/v1/admin/enquiries/test-uuid']] as [$method, $path]) {
                self::assertSame(401, $app->handle(new Request($method, $path))->status());
            }
        } finally {
            if ($previous === null) {
                unset($_ENV['JWT_SECRET']);
            } else {
                $_ENV['JWT_SECRET'] = $previous;
            }
        }
    }

    public function testViewerCannotUpdateStatus(): void
    {
        $request = new Request('PATCH', '/api/v1/admin/enquiries/test-uuid');
        $request->setAttribute('admin_user', new \PerrymanFinance\Domain\Identity\AdminUser(1, 'viewer', 'viewer@example.test', '', 'Viewer', 'active', ['viewer'], ['enquiries.view']));
        $middleware = new \PerrymanFinance\Http\Middleware\PermissionMiddleware('enquiries.update');
        $this->expectException(\PerrymanFinance\Http\Exceptions\ForbiddenException::class);
        $middleware->process($request, static fn () => new \PerrymanFinance\Http\Response([], 200));
    }
}
