<?php

declare(strict_types=1);

namespace PerrymanFinance\Http;

final readonly class Route
{
    /**
     * @param callable|array{class-string, string} $handler
     * @param list<class-string|object> $middleware
     */
    public function __construct(
        public string $method,
        public string $path,
        public mixed $handler,
        public array $middleware = [],
    ) {
    }

    /** @return array<string, string>|null */
    public function match(string $path): ?array
    {
        $pattern = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $this->path,
        );
        if (!is_string($pattern) || preg_match('#^' . $pattern . '/?$#', $path, $matches) !== 1) {
            return null;
        }
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = rawurldecode($value);
            }
        }
        return $params;
    }
}
