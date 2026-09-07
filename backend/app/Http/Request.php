<?php

declare(strict_types=1);

namespace PerrymanFinance\Http;

final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     * @param array<string, string> $routeParams
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $headers = [],
        private readonly array $query = [],
        private array $body = [],
        private readonly array $files = [],
        private readonly string $rawBody = '',
        private array $routeParams = [],
        private array $attributes = [],
    ) {
    }

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $query
     * @param array<string, mixed> $files
     */
    public static function fromGlobals(array $server, array $query, array $files, string $rawBody): self
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }
        $uri = parse_url(is_string($server['REQUEST_URI'] ?? null) ? $server['REQUEST_URI'] : '/', PHP_URL_PATH);
        return new self(
            strtoupper(is_string($server['REQUEST_METHOD'] ?? null) ? $server['REQUEST_METHOD'] : 'GET'),
            is_string($uri) ? $uri : '/',
            $headers,
            $query,
            [],
            $files,
            $rawBody,
        );
    }

    public function method(): string
    {
        return $this->method;
    }
    public function uri(): string
    {
        return $this->uri;
    }
    public function rawBody(): string
    {
        return $this->rawBody;
    }
    /** @return array<string, mixed> */ public function body(): array
    {
        return $this->body;
    }
    /** @return array<string, mixed> */ public function query(): array
    {
        return $this->query;
    }
    /** @return array<string, mixed> */ public function files(): array
    {
        return $this->files;
    }
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }
    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
        return $default;
    }

    /** @param array<string, mixed> $body */
    public function setBody(array $body): void
    {
        $this->body = $body;
    }
    /** @param array<string, string> $params */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
}
