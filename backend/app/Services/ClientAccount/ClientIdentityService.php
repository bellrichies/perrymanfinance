<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\ClientAccount;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Config\Config;
use PerrymanFinance\Database\TransactionManager;
use PerrymanFinance\Domain\ClientAccount\ClientUser;
use PerrymanFinance\Http\Exceptions\UnauthorizedException;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Repositories\ClientAuditRepository;
use PerrymanFinance\Repositories\ClientTokenRepository;
use PerrymanFinance\Repositories\ClientUserRepository;
use PerrymanFinance\Repositories\RateLimitRepository;
use PerrymanFinance\Services\Identity\RateLimiter;
use PerrymanFinance\Services\Identity\TokenService;
use Throwable;

final readonly class ClientIdentityService
{
    public function __construct(
        private ClientUserRepository $users,
        private ClientTokenRepository $tokensRepository,
        private ClientAuditRepository $audit,
        private TokenService $tokens,
        private RateLimiter $limiter,
        private TransactionManager $transactions,
        private ClientAccountNotifierInterface $notifier,
        private Config $config,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function register(array $input, string $ip, ?string $requestId): void
    {
        $email = $this->email($input['email'] ?? null);
        $password = $this->password($input['password'] ?? null);
        if (($input['password_confirmation'] ?? null) !== $password) {
            throw new ValidationException(['password_confirmation' => ['Passwords must match.']]);
        }
        if (empty($input['consent_terms'])) {
            throw new ValidationException(['consent_terms' => ['You must acknowledge the client account terms and risk disclosures.']]);
        }
        $first = $this->text($input['first_name'] ?? null, 'first_name', 120);
        $last = $this->text($input['last_name'] ?? null, 'last_name', 120);
        $this->limiter->hit('client-register', $email . '|' . $ip, 5, 900);
        if ($this->users->findByEmail($email) !== null) {
            throw new ValidationException(['email' => ['A client account could not be created with this email address.']]);
        }
        $now = $this->now();
        $raw = $this->tokens->randomToken();
        $clientId = $this->transactions->run(function () use ($email, $password, $first, $last, $input, $raw, $now, $ip, $requestId): int {
            $clientId = $this->users->create($this->uuid(), $email, password_hash($password, PASSWORD_DEFAULT), [
                'first_name' => $first,
                'last_name' => $last,
                'phone' => is_string($input['phone'] ?? null) ? trim($input['phone']) : null,
                'country' => is_string($input['country'] ?? null) ? trim($input['country']) : null,
                'consent_marketing' => !empty($input['consent_marketing']),
            ], $now);
            $this->tokensRepository->replaceVerificationToken($clientId, $this->tokens->hashOpaqueToken($raw), $this->future(86400), $now);
            $this->audit->record('client', $clientId, $clientId, 'client.registered', ['ip_address' => $ip, 'request_id' => $requestId], $now);
            return $clientId;
        });
        try {
            $this->notifier->sendVerification($email, "{$first} {$last}", $this->config->string('app.frontend_url') . '/client/verify-email?token=' . rawurlencode($raw));
        } catch (Throwable) {
            $this->audit->record('system', null, $clientId, 'client.verification_delivery_failed', ['ip_address' => $ip, 'request_id' => $requestId], $this->now());
        }
    }

    public function verifyEmail(string $rawToken, string $ip, ?string $requestId): void
    {
        $now = $this->now();
        $row = $this->tokensRepository->findVerificationToken($this->tokens->hashOpaqueToken($rawToken), $now);
        if ($row === null) {
            throw new ValidationException(['token' => ['The verification link is invalid or expired.']]);
        }
        $clientId = (int) $row['client_user_id'];
        $this->users->verifyEmail($clientId, $now);
        $this->tokensRepository->markVerificationUsed((int) $row['id'], $now);
        $this->audit->record('client', $clientId, $clientId, 'client.email_verified', ['ip_address' => $ip, 'request_id' => $requestId], $now);
    }

    /** @return array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} */
    public function login(string $email, string $password, string $ip, ?string $requestId): array
    {
        $email = $this->email($email);
        $this->limiter->hit('client-login', $email . '|' . $ip, (int) $this->config->get('auth.login_limit', 5), 900);
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user->passwordHash) || !$user->canLogin()) {
            $this->audit->record('client', $user?->id, $user?->id, 'client.login_failed', ['ip_address' => $ip, 'request_id' => $requestId], $this->now());
            throw new UnauthorizedException('The supplied credentials are invalid or the account is not verified.');
        }
        $this->users->recordLogin($user->id, $this->now());
        $this->audit->record('client', $user->id, $user->id, 'client.login_succeeded', ['ip_address' => $ip, 'request_id' => $requestId], $this->now());
        return $this->createSession($user);
    }

    /** @return array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} */
    public function refresh(string $rawToken, string $ip, ?string $requestId): array
    {
        return $this->transactions->run(function () use ($rawToken, $ip, $requestId): array {
            $row = $this->tokensRepository->findSession($this->tokens->hashOpaqueToken($rawToken));
            $now = $this->now();
            if ($row === null || $row['revoked_at'] !== null || (string) $row['expires_at'] <= $now) {
                throw new UnauthorizedException('The session has expired.');
            }
            $user = $this->users->findById((int) $row['client_user_id']);
            if ($user === null || !$user->canLogin()) {
                throw new UnauthorizedException('The session has expired.');
            }
            $newRaw = $this->tokens->randomToken();
            $newId = $this->tokensRepository->createSession($user->id, $this->tokens->hashOpaqueToken($newRaw), $this->future((int) $this->config->get('auth.refresh_ttl', 1209600)), $now);
            $this->tokensRepository->rotateSession((int) $row['id'], $newId, $now);
            $this->audit->record('client', $user->id, $user->id, 'client.token_refreshed', ['ip_address' => $ip, 'request_id' => $requestId], $now);
            return ['user' => $user->publicData(), 'access' => $this->tokens->issueAccessToken($user->id, $user->uuid, ['client']), 'refresh_token' => $newRaw];
        });
    }

    public function logout(string $rawToken, ?ClientUser $user, string $ip, ?string $requestId): void
    {
        if ($rawToken !== '') {
            $this->tokensRepository->revokeSession($this->tokens->hashOpaqueToken($rawToken), $this->now());
        }
        $this->audit->record('client', $user?->id, $user?->id, 'client.logout', ['ip_address' => $ip, 'request_id' => $requestId], $this->now());
    }

    public function forgotPassword(string $email, string $ip, ?string $requestId): void
    {
        $email = $this->email($email);
        $this->limiter->hit('client-password-reset', $email . '|' . $ip, 3, 900);
        $user = $this->users->findByEmail($email);
        if ($user === null || $user->status === 'closed') {
            return;
        }
        $raw = $this->tokens->randomToken();
        $this->tokensRepository->replaceResetToken($user->id, $this->tokens->hashOpaqueToken($raw), $this->future(3600), $this->now());
        $this->notifier->sendPasswordReset($user->email, trim(($user->firstName ?? '') . ' ' . ($user->lastName ?? '')), $this->config->string('app.frontend_url') . '/client/reset-password?token=' . rawurlencode($raw));
        $this->audit->record('client', $user->id, $user->id, 'client.password_reset_requested', ['ip_address' => $ip, 'request_id' => $requestId], $this->now());
    }

    public function resetPassword(string $rawToken, string $password, string $ip, ?string $requestId): void
    {
        $password = $this->password($password);
        $now = $this->now();
        $row = $this->tokensRepository->findResetToken($this->tokens->hashOpaqueToken($rawToken), $now);
        if ($row === null) {
            throw new ValidationException(['token' => ['The reset link is invalid or expired.']]);
        }
        $clientId = (int) $row['client_user_id'];
        $this->users->updatePassword($clientId, password_hash($password, PASSWORD_DEFAULT), $now);
        $this->tokensRepository->markResetUsed((int) $row['id'], $now);
        $this->tokensRepository->revokeAllSessions($clientId, $now);
        $this->audit->record('client', $clientId, $clientId, 'client.password_reset_completed', ['ip_address' => $ip, 'request_id' => $requestId], $now);
    }

    /** @return array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} */
    private function createSession(ClientUser $user): array
    {
        $raw = $this->tokens->randomToken();
        $this->tokensRepository->createSession($user->id, $this->tokens->hashOpaqueToken($raw), $this->future((int) $this->config->get('auth.refresh_ttl', 1209600)), $this->now());
        return ['user' => $user->publicData(), 'access' => $this->tokens->issueAccessToken($user->id, $user->uuid, ['client']), 'refresh_token' => $raw];
    }

    private function email(mixed $value): string
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(['email' => ['Enter a valid email address.']]);
        }
        return strtolower(trim($value));
    }

    private function password(mixed $value): string
    {
        if (!is_string($value) || strlen($value) < 12 || !preg_match('/[a-z]/', $value) || !preg_match('/[A-Z]/', $value) || !preg_match('/\d/', $value)) {
            throw new ValidationException(['password' => ['Use at least 12 characters with upper-case, lower-case, and a number.']]);
        }
        return $value;
    }

    private function text(mixed $value, string $field, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > $max) {
            throw new ValidationException([$field => ['This field is required.']]);
        }
        return trim($value);
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    private function future(int $seconds): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("+{$seconds} seconds")->format('Y-m-d H:i:s.u');
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
