<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Http\Exceptions\TooManyRequestsException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Logging\LoggerInterface;
use PerrymanFinance\Repositories\ContentRepository;
use PerrymanFinance\Repositories\EnquiryRepository;
use PerrymanFinance\Repositories\RateLimitRepository;
use PerrymanFinance\Services\Enquiries\EnquiryNotifier;
use PerrymanFinance\Services\Enquiries\EnquiryService;
use PerrymanFinance\Services\Identity\RateLimiter;
use PerrymanFinance\Validation\Validator;
use PHPUnit\Framework\TestCase;
use Tests\Support\SqliteConnection;

final class EnquirySubmissionTest extends TestCase
{
    private PDO $pdo;
    private EnquiryService $service;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE enquiries (uuid TEXT,name TEXT,email TEXT,phone TEXT,enquiry_type TEXT,subject TEXT,message TEXT,source_page TEXT,consent_at TEXT,status TEXT,created_at TEXT,updated_at TEXT)');
        $this->pdo->exec('CREATE TABLE rate_limit_counters (counter_key TEXT PRIMARY KEY,attempts INTEGER,window_started_at TEXT,expires_at TEXT)');
        $this->pdo->exec('CREATE TABLE site_settings (setting_key TEXT,value_json TEXT,is_public INTEGER)');
        $this->pdo->exec('CREATE TABLE legal_documents (slug TEXT,status TEXT,effective_at TEXT,published_at TEXT)');
        $this->pdo->exec("INSERT INTO site_settings VALUES ('enquiry_consent','\"Test consent\"',1)");
        $this->pdo->exec("INSERT INTO legal_documents VALUES ('privacy-policy','published',NULL,'2026-01-01')");
        $notifier = new class (new Config([])) extends EnquiryNotifier {
            public function notify(): void
            {
                throw new \RuntimeException('Test mail outage');
            }
        };
        $this->service = new EnquiryService(new EnquiryRepository($connection), new ContentRepository($connection), new Validator(), new RateLimiter(new RateLimitRepository($connection)), $notifier, $this->createMock(LoggerInterface::class));
    }

    public function testSubmissionSurvivesNotificationFailureAndUsesPreparedStatements(): void
    {
        $this->service->submit($this->input(), '127.0.0.1');
        $statement = $this->pdo->query('SELECT * FROM enquiries');
        self::assertInstanceOf(\PDOStatement::class, $statement);
        $row = $statement->fetch();
        self::assertSame("O'Reilly", $row['name']);
        self::assertSame('new', $row['status']);
        self::assertNotEmpty($row['consent_at']);
        self::assertSame('Please explain the risks.', $row['message']);
    }

    public function testConsentCannotBeBypassed(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->submit([...$this->input(), 'consent' => false], '127.0.0.1');
    }

    public function testMissingPublishedPrivacyBlocksSubmission(): void
    {
        $this->pdo->exec("UPDATE legal_documents SET status='draft'");
        $this->expectException(ValidationException::class);
        $this->service->submit($this->input(), '127.0.0.1');
    }

    public function testInvalidEmailAndOversizedMessageAreRejected(): void
    {
        try {
            $this->service->submit([...$this->input(), 'email' => 'invalid', 'message' => str_repeat('x', 5001)], '127.0.0.1');
            self::fail('Invalid input accepted');
        } catch (ValidationException) {
            self::assertSame(0, $this->countEnquiries());
        }
    }

    public function testHoneypotDoesNotPersistAndRequestsAreThrottled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->submit([...$this->input(), 'website' => 'spam'], '127.0.0.1');
        }
        self::assertSame(0, $this->countEnquiries());
        $this->expectException(TooManyRequestsException::class);
        $this->service->submit($this->input(), '127.0.0.1');
    }

    private function countEnquiries(): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM enquiries');
        self::assertInstanceOf(\PDOStatement::class, $statement);
        return (int) $statement->fetchColumn();
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        return ['name' => "O'Reilly", 'email' => 'visitor@example.test', 'subject' => 'Information', 'enquiry_type' => 'general', 'message' => 'Please explain the risks.', 'consent' => true];
    }
}
