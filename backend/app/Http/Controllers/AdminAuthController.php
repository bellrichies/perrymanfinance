<?php

declare(strict_types=1);

namespace PerrymanFinance\Http\Controllers;

use PerrymanFinance\Config\Config;
use PerrymanFinance\Domain\Identity\AdminUser;
use PerrymanFinance\Http\ApiResponseFactory;
use PerrymanFinance\Http\Exceptions\ValidationException;
use PerrymanFinance\Http\Request;
use PerrymanFinance\Http\Response;
use PerrymanFinance\Services\Identity\AuthService;

final readonly class AdminAuthController
{
    public function __construct(private AuthService $auth, private ApiResponseFactory $responses, private Config $config)
    {
    }

    public function login(Request $request): Response
    {
        $email = $this->requiredString($request, 'email');
        $password = $this->requiredString($request, 'password');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(['email' => ['Enter a valid email address.']]);
        }
        return $this->sessionResponse($this->auth->login($email, $password, $this->ip($request), $this->requestId($request)));
    }

    public function refresh(Request $request): Response
    {
        $token = $this->cookie($request, 'pf_admin_refresh');
        if ($token === '') {
            throw new \PerrymanFinance\Http\Exceptions\UnauthorizedException('The session has expired.');
        }
        return $this->sessionResponse($this->auth->refresh($token, $this->ip($request), $this->requestId($request)));
    }

    public function logout(Request $request): Response
    {
        $user = $request->attribute('admin_user');
        $this->auth->logout($this->cookie($request, 'pf_admin_refresh'), $user instanceof AdminUser ? $user : null, $this->ip($request), $this->requestId($request));
        return $this->responses->success(null, [], 'Signed out.')->withHeader('Set-Cookie', $this->expiredCookie());
    }

    public function forgotPassword(Request $request): Response
    {
        $email = $this->requiredString($request, 'email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(['email' => ['Enter a valid email address.']]);
        }
        $this->auth->forgotPassword($email, $this->ip($request), $this->requestId($request));
        return $this->responses->success(null, [], 'If the account is eligible, password reset instructions will be sent.');
    }

    public function resetPassword(Request $request): Response
    {
        $this->auth->resetPassword($this->requiredString($request, 'token'), $this->requiredString($request, 'password'), $this->ip($request), $this->requestId($request));
        return $this->responses->success(null, [], 'Password reset complete. Please sign in again.')->withHeader('Set-Cookie', $this->expiredCookie());
    }

    public function me(Request $request): Response
    {
        $user = $request->attribute('admin_user');
        return $this->responses->success($user instanceof AdminUser ? $user->publicData() : null);
    }

    /** @param array{user: array<string, mixed>, access: array<string, mixed>, refresh_token: string} $session */
    private function sessionResponse(array $session): Response
    {
        return $this->responses->success(['user' => $session['user'], ...$session['access']])
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Set-Cookie', $this->refreshCookie($session['refresh_token']));
    }

    private function requiredString(Request $request, string $field): string
    {
        $value = $request->input($field);
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException([$field => ['This field is required.']]);
        }
        return trim($value);
    }

    private function cookie(Request $request, string $name): string
    {
        $cookies = [];
        parse_str(str_replace('; ', '&', $request->header('Cookie', '') ?? ''), $cookies);
        return is_string($cookies[$name] ?? null) ? $cookies[$name] : '';
    }

    private function refreshCookie(string $token): string
    {
        $secure = $this->config->bool('auth.cookie_secure', true) ? '; Secure' : '';
        $ttl = (int) $this->config->get('auth.refresh_ttl', 1209600);
        return 'pf_admin_refresh=' . rawurlencode($token) . "; Max-Age={$ttl}; Path=/api/v1/admin/auth; HttpOnly; SameSite=Strict{$secure}";
    }

    private function expiredCookie(): string
    {
        $secure = $this->config->bool('auth.cookie_secure', true) ? '; Secure' : '';
        return 'pf_admin_refresh=; Max-Age=0; Path=/api/v1/admin/auth; HttpOnly; SameSite=Strict' . $secure;
    }

    private function ip(Request $request): string
    {
        $value = $request->attribute('client_ip', 'unknown');
        return is_string($value) ? substr($value, 0, 45) : 'unknown';
    }

    private function requestId(Request $request): ?string
    {
        $value = $request->attribute('request_id');
        return is_string($value) ? $value : null;
    }
}
