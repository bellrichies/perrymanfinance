<?php

declare(strict_types=1);

namespace PerrymanFinance\Services\Identity;

use DateTimeImmutable;
use DateTimeZone;
use PerrymanFinance\Http\Exceptions\TooManyRequestsException;
use PerrymanFinance\Repositories\RateLimitRepository;

final readonly class RateLimiter
{
    public function __construct(private RateLimitRepository $repository)
    {
    }

    public function hit(string $scope, string $identity, int $limit, int $windowSeconds): string
    {
        $key = hash('sha256', $scope . '|' . mb_strtolower(trim($identity)));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $row = $this->repository->find($key);
        if ($row === null || new DateTimeImmutable((string) $row['expires_at'], new DateTimeZone('UTC')) <= $now) {
            $this->repository->start($key, $this->format($now), $this->format($now->modify("+{$windowSeconds} seconds")));
            return $key;
        }
        if ((int) $row['attempts'] >= $limit) {
            $retry = max(1, (new DateTimeImmutable((string) $row['expires_at']))->getTimestamp() - $now->getTimestamp());
            throw new TooManyRequestsException($retry);
        }
        $this->repository->increment($key);
        return $key;
    }

    public function clear(string $key): void
    {
        $this->repository->clear($key);
    }

    private function format(DateTimeImmutable $date): string
    {
        return $date->format('Y-m-d H:i:s.u');
    }
}
