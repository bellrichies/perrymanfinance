<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Identity;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\AdminUserRepository;
use PerrymanFinance\Repositories\AuditLogRepository;
use PerrymanFinance\Repositories\PasswordResetRepository;
use PerrymanFinance\Repositories\RefreshTokenRepository;
use Throwable;

final readonly class AuthService
{
    public function __construct(
        private AdminUserRepository $users,
        private RefreshTokenRepository $refreshTokens,
        private PasswordResetRepository $resetTokens,
        private AuditLogRepository $audit,
        private TokenService $tokens,
        private RateLimiter $limiter,
        private TransactionManager $transactions,
        private PasswordResetNotifierInterface $notifier,
        private Config $config,
    ) {
    }

    /** @return array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} */
    public function login(string $email, string $password, string $ipAddress, ?string $requestId): array
    {
        $limitKey = $this->limiter->hit('admin-login', $email . '|' . $ipAddress, (int) $this->config->get('auth.login_limit', 5), (int) $this->config->get('auth.rate_window', 900));
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user->passwordHash) || !$user->isActive()) {
            $this->audit->record($user?->id, 'auth.login_failed', ['request_id' => $requestId, 'ip_address' => $ipAddress], $this->now());
            throw new UnauthorizedException('The supplied credentials are invalid.');
        }
        $this->limiter->clear($limitKey);
        $this->users->recordLogin($user->id, $this->now());
        $session = $this->createSession($user);
        $this->audit->record($user->id, 'auth.login_succeeded', ['request_id' => $requestId, 'ip_address' => $ipAddress], $this->now());
        return $session;
    }

    /** @return array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} */
    public function refresh(string $rawToken, string $ipAddress, ?string $requestId): array
    {
        return $this->transactions->run(function () use ($rawToken, $ipAddress, $requestId): array {
            $row = $this->refreshTokens->find($this->tokens->hashOpaqueToken($rawToken));
            $now = $this->now();
            if ($row === null || $row['revoked_at'] !== null || (string) $row['expires_at'] <= $now) {
                if ($row !== null && $row['revoked_at'] !== null) {
                    $this->refreshTokens->revokeAllForUser((int) $row['admin_user_id'], $now);
                }
                throw new UnauthorizedException('The session has expired.');
            }
            $user = $this->users->findById((int) $row['admin_user_id']);
            if ($user === null || !$user->isActive()) {
                $this->refreshTokens->revokeByHash($this->tokens->hashOpaqueToken($rawToken), $now);
                throw new UnauthorizedException('The session has expired.');
            }
            $newRaw = $this->tokens->randomToken();
            $newId = $this->refreshTokens->create($user->id, $this->tokens->hashOpaqueToken($newRaw), $this->future((int) $this->config->get('auth.refresh_ttl', 1209600)), $now);
            if (!$this->refreshTokens->rotate((int) $row['id'], $newId, $now)) {
                throw new UnauthorizedException('The session has expired.');
            }
            $this->audit->record($user->id, 'auth.token_refreshed', ['request_id' => $requestId, 'ip_address' => $ipAddress], $now);
            return ['user' => $user->publicData(), 'access' => $this->tokens->issueAccessToken($user->id, $user->uuid, $user->permissions), 'refresh_token' => $newRaw];
        });
    }

    public function logout(string $rawToken, ?AdminUser $user, string $ipAddress, ?string $requestId): void
    {
        if ($rawToken !== '') {
            $this->refreshTokens->revokeByHash($this->tokens->hashOpaqueToken($rawToken), $this->now());
        }
        $this->audit->record($user?->id, 'auth.logout', ['request_id' => $requestId, 'ip_address' => $ipAddress], $this->now());
    }

    public function forgotPassword(string $email, string $ipAddress, ?string $requestId): void
    {
        $this->limiter->hit('admin-password-reset', $email . '|' . $ipAddress, (int) $this->config->get('auth.reset_limit', 3), (int) $this->config->get('auth.rate_window', 900));
        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->isActive()) {
            return;
        }
        $raw = $this->tokens->randomToken();
        $this->resetTokens->replaceForUser($user->id, $this->tokens->hashOpaqueToken($raw), $this->future((int) $this->config->get('auth.reset_ttl', 3600)), $this->now());
        try {
            $url = $this->config->string('app.frontend_url')
                . '/admin/reset-password?token=' . rawurlencode($raw);
            $this->notifier->send($user->email, $user->displayName, $url);
            $this->audit->record($user->id, 'auth.password_reset_requested', ['request_id' => $requestId, 'ip_address' => $ipAddress], $this->now());
        } catch (Throwable) {
            $this->audit->record($user->id, 'auth.password_reset_delivery_failed', ['request_id' => $requestId, 'ip_address' => $ipAddress], $this->now());
        }
    }

    public function resetPassword(string $rawToken, string $password, string $ipAddress, ?string $requestId): void
    {
        if (strlen($password) < 12 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
            throw new ValidationException(['password' => ['Use at least 12 characters with upper-case, lower-case, and a number.']]);
        }
        $this->transactions->run(function () use ($rawToken, $password, $ipAddress, $requestId): void {
            $row = $this->resetTokens->findUsable($this->tokens->hashOpaqueToken($rawToken), $this->now());
            if ($row === null) {
                throw new ValidationException(['token' => ['The reset link is invalid or expired.']]);
            }
            $userId = (int) $row['admin_user_id'];
            $this->users->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT), $this->now());
            $this->resetTokens->markUsed((int) $row['id'], $this->now());
            $this->refreshTokens->revokeAllForUser($userId, $this->now());
            $this->audit->record($userId, 'auth.password_reset_completed', ['request_id' => $requestId, 'ip_address' => $ipAddress], $this->now());
        });
    }

    /** @return array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} */
    private function createSession(AdminUser $user): array
    {
        $raw = $this->tokens->randomToken();
        $this->refreshTokens->create($user->id, $this->tokens->hashOpaqueToken($raw), $this->future((int) $this->config->get('auth.refresh_ttl', 1209600)), $this->now());
        return ['user' => $user->publicData(), 'access' => $this->tokens->issueAccessToken($user->id, $user->uuid, $user->permissions), 'refresh_token' => $raw];
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    private function future(int $seconds): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("+{$seconds} seconds")->format('Y-m-d H:i:s.u');
    }
}
