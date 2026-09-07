<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class RateLimitRepository extends AbstractRepository
{
    /** @return array<string, mixed>|null */
    public function find(string $key): ?array
    {
        $row = $this->execute('SELECT * FROM rate_limit_counters WHERE counter_key = :key', ['key' => $key])->fetch();
        return is_array($row) ? $row : null;
    }

    public function start(string $key, string $now, string $expires): void
    {
        $updated = $this->execute(
            'UPDATE rate_limit_counters SET attempts = 1, window_started_at = :now, expires_at = :expires '
            . 'WHERE counter_key = :key',
            ['key' => $key, 'now' => $now, 'expires' => $expires],
        );
        if ($updated->rowCount() === 0) {
            $this->execute(
                'INSERT INTO rate_limit_counters (counter_key, attempts, window_started_at, expires_at) '
                . 'VALUES (:key, 1, :now, :expires)',
                ['key' => $key, 'now' => $now, 'expires' => $expires],
            );
        }
    }

    public function increment(string $key): void
    {
        $this->execute('UPDATE rate_limit_counters SET attempts = attempts + 1 WHERE counter_key = :key', ['key' => $key]);
    }

    public function clear(string $key): void
    {
        $this->execute('DELETE FROM rate_limit_counters WHERE counter_key = :key', ['key' => $key]);
    }
}
