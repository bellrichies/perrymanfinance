<?php

declare(strict_types=1);

namespace PerrymanFinance\Http;

final class ApiResponseFactory
{
    /**
     * @param array<string, mixed>|list<mixed>|null $data
     * @param array<string, mixed> $meta
     */
    public function success(
        array|null $data = null,
        array $meta = [],
        ?string $message = null,
        int $status = 200,
    ): Response {
        return new Response(
            ['success' => true, 'data' => $data, 'meta' => (object) $meta, 'message' => $message],
            $status,
        );
    }

    /** @param array<string, list<string>> $fields */
    public function error(string $code, string $message, int $status, array $fields = []): Response
    {
        return new Response(
            ['success' => false, 'error' => ['code' => $code, 'message' => $message, 'fields' => (object) $fields]],
            $status,
        );
    }
}
