<?php

declare(strict_types=1);

namespace PerrymanFinance\Logging;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RuntimeException;

final readonly class JsonLogger implements LoggerInterface
{
    public function __construct(private string $path)
    {
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the log directory.');
        }
        try {
            $line = json_encode([
                'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
                'level' => strtolower($level), 'message' => $message, 'context' => $this->sanitize($context),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode log entry.', 0, $exception);
        }
        if (file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the log entry.');
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        foreach ($context as $key => $value) {
            if (preg_match('/password|token|secret|authorization/i', $key) === 1) {
                $context[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $context[$key] = $this->sanitize($value);
            }
        }
        return $context;
    }
}
