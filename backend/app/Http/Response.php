<?php

declare(strict_types=1);

namespace PerrymanFinance\Http;

use JsonException;

final readonly class Response
{
    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    public function __construct(private array $body, private int $status = 200, private array $headers = [])
    {
    }
    public function status(): int
    {
        return $this->status;
    }
    /** @return array<string, mixed> */ public function body(): array
    {
        return $this->body;
    }
    /** @return array<string, string> */ public function headers(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->body, $this->status, [...$this->headers, $name => $value]);
    }

    /** @throws JsonException */
    public function send(): void
    {
        http_response_code($this->status);
        foreach (['Content-Type' => 'application/json; charset=utf-8', ...$this->headers] as $name => $value) {
            header("{$name}: {$value}");
        }
        echo json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
