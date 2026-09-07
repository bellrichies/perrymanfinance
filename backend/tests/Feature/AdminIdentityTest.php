<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\Exceptions\ForbiddenException;
use PerrymanFinance\Http\Exceptions\TooManyRequestsException;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Middleware\PermissionMiddleware;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Repositories\AdminUserRepository;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\PasswordResetRepository;
use PerrymanFinance\Repositories\RateLimitRepository;
use PerrymanFinance\Repositories\RefreshTokenRepository;
use PerrymanFinance\Services\Identity\AuthService;
use PerrymanFinance\Services\Identity\RateLimiter;
use PerrymanFinance\Services\Identity\TokenService;
use PHPUnit\Framework\TestCase;
use Tests\Support\SqliteConnection;
use Tests\Support\CapturingResetNotifier;

final class AdminIdentityTest extends TestCase
{
    private PDO $pdo;
    private AuthService $auth;
    private CapturingResetNotifier $notifier;

    protected function setUp(): void
    {
        $connection = SqliteConnection::memory();
        $this->pdo = $connection->connection();
        $this->createSchema();
        $config = new Config([
            'app' => ['url' => 'https://api.example.test', 'frontend_url' => 'https://example.test'],
            'auth' => [
                'jwt_secret' => str_repeat('s', 48), 'access_ttl' => 60, 'refresh_ttl' => 3600,
                'reset_ttl' => 600, 'login_limit' => 2, 'reset_limit' => 2, 'rate_window' => 900,
            ],
        ]);
        $this->notifier = new CapturingResetNotifier();
        $this->auth = new AuthService(
            new AdminUserRepository($connection),
            new RefreshTokenRepository($connection),
            new PasswordResetRepository($connection),
            new AuditLogRepository($connection),
            new TokenService($config),
            new RateLimiter(new RateLimitRepository($connection)),
            new TransactionManager($connection),
            $this->notifier,
            $config,
        );
        $this->insertUser('admin@example.test', 'Correct-password-123', 'active');
    }

    public function testValidLoginRefreshAndLogoutRotation(): void
    {
        $login = $this->auth->login('admin@example.test', 'Correct-password-123', '127.0.0.1', 'req-1');
        self::assertSame('admin@example.test', $login['user']['email']);
        self::assertNotEmpty($login['access']['access_token']);
        $refresh = $this->auth->refresh($login['refresh_token'], '127.0.0.1', 'req-2');
        self::assertNotSame($login['refresh_token'], $refresh['refresh_token']);
        $this->expectException(UnauthorizedException::class);
        $this->auth->refresh($login['refresh_token'], '127.0.0.1', 'req-3');
    }

    public function testInvalidLoginAndInactiveAdminAreRejected(): void
    {
        try {
            $this->auth->login('admin@example.test', 'wrong', '127.0.0.1', null);
            self::fail('Invalid password was accepted.');
        } catch (UnauthorizedException $exception) {
            self::assertSame(401, $exception->status);
        }
        $this->pdo->exec("UPDATE admin_users SET status = 'inactive'");
        $this->expectException(UnauthorizedException::class);
        $this->auth->login('admin@example.test', 'Correct-password-123', '127.0.0.2', null);
    }

    public function testLogoutRevokesRefreshToken(): void
    {
        $session = $this->auth->login('admin@example.test', 'Correct-password-123', '127.0.0.1', null);
        $this->auth->logout($session['refresh_token'], null, '127.0.0.1', null);
        $this->expectException(UnauthorizedException::class);
        $this->auth->refresh($session['refresh_token'], '127.0.0.1', null);
    }

    public function testExpiredRefreshTokenIsRejected(): void
    {
        $session = $this->auth->login('admin@example.test', 'Correct-password-123', '127.0.0.1', null);
        $this->pdo->exec("UPDATE refresh_tokens SET expires_at = '2000-01-01 00:00:00.000000'");
        $this->expectException(UnauthorizedException::class);
        $this->auth->refresh($session['refresh_token'], '127.0.0.1', null);
    }

    public function testRateLimitingReturnsTooManyRequests(): void
    {
        $denials = 0;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $this->auth->login('missing@example.test', 'wrong', '192.0.2.1', null);
            } catch (UnauthorizedException) {
                $denials++;
            }
        }
        self::assertSame(2, $denials);
        $this->expectException(TooManyRequestsException::class);
        $this->auth->login('missing@example.test', 'wrong', '192.0.2.1', null);
    }

    public function testPasswordResetChangesPasswordAndRevokesSessions(): void
    {
        $session = $this->auth->login('admin@example.test', 'Correct-password-123', '127.0.0.1', null);
        $this->auth->forgotPassword('admin@example.test', '127.0.0.1', null);
        self::assertNotNull($this->notifier->url);
        $queryString = parse_url((string) $this->notifier->url, PHP_URL_QUERY);
        self::assertIsString($queryString);
        parse_str($queryString, $query);
        self::assertIsString($query['token'] ?? null);
        $this->auth->resetPassword($query['token'], 'New-secure-password-456', '127.0.0.1', null);
        $this->expectException(UnauthorizedException::class);
        $this->auth->refresh($session['refresh_token'], '127.0.0.1', null);
    }

    public function testForgotPasswordDoesNotRevealUnknownAccount(): void
    {
        $this->auth->forgotPassword('missing@example.test', '127.0.0.1', null);
        self::assertNull($this->notifier->url);
    }

    public function testPermissionMiddlewareDeniesMissingPermission(): void
    {
        $user = new AdminUser(1, 'uuid', 'a@b.test', 'hash', 'Admin', 'active', ['viewer'], ['pages.view']);
        $request = new Request('GET', '/admin/users');
        $request->setAttribute('admin_user', $user);
        $this->expectException(ForbiddenException::class);
        (new PermissionMiddleware('users.manage'))->process($request, static fn (): Response => new Response([]));
    }

    private function insertUser(string $email, string $password, string $status): void
    {
        $statement = $this->pdo->prepare('INSERT INTO admin_users VALUES (1, :uuid, :email, :hash, :name, :status, NULL, NULL, NULL, NULL)');
        $statement->execute(['uuid' => '11111111-1111-4111-8111-111111111111', 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'name' => 'Test Admin', 'status' => $status]);
        $this->pdo->exec("INSERT INTO roles VALUES (1, 'viewer')");
        $this->pdo->exec("INSERT INTO permissions VALUES (1, 'pages.view')");
        $this->pdo->exec('INSERT INTO user_roles VALUES (1, 1)');
        $this->pdo->exec('INSERT INTO role_permissions VALUES (1, 1)');
    }

    private function createSchema(): void
    {
        foreach (
            [
            'CREATE TABLE admin_users (id INTEGER PRIMARY KEY, uuid TEXT, email TEXT, password_hash TEXT, display_name TEXT, status TEXT, last_login_at TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT)', 'CREATE TABLE permissions (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE user_roles (admin_user_id INTEGER, role_id INTEGER)', 'CREATE TABLE role_permissions (role_id INTEGER, permission_id INTEGER)',
            'CREATE TABLE refresh_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_user_id INTEGER, token_hash TEXT UNIQUE, expires_at TEXT, revoked_at TEXT, replaced_by_id INTEGER, created_at TEXT)',
            'CREATE TABLE password_reset_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_user_id INTEGER, token_hash TEXT UNIQUE, expires_at TEXT, used_at TEXT, created_at TEXT)',
            'CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER, event TEXT, subject_type TEXT, subject_id TEXT, request_id TEXT, ip_address TEXT, before_json TEXT, after_json TEXT, created_at TEXT)',
            'CREATE TABLE rate_limit_counters (counter_key TEXT PRIMARY KEY, attempts INTEGER, window_started_at TEXT, expires_at TEXT)',
            ] as $sql
        ) {
            $this->pdo->exec($sql);
        }
    }
}
