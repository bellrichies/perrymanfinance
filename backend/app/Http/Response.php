<?php

declare(strict_types=1);

namespace PerrymanFinance\Http;

use JsonException;

final readonly class Response
{
    /**
     * @param array<string, mixed>|string $body
     * @param array<string, string> $headers
     */
    public function __construct(private array|string $body, private int $status = 200, private array $headers = [])
    {
    }
    public function status(): int
    {
        return $this->status;
    }
    /** @return array<string, mixed>|string */ public function body(): array|string
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
        $defaultContentType = is_string($this->body) ? 'text/plain; charset=utf-8' : 'application/json; charset=utf-8';
        foreach (['Content-Type' => $defaultContentType, ...$this->headers] as $name => $value) {
            header("{$name}: {$value}");
        }
        if (is_string($this->body)) {
            echo $this->body;
            return;
        }
        echo json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
