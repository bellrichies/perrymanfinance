<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class AuditLogRepository extends AbstractRepository
{
    /** @param array<string, mixed> $context */
    public function record(?int $actorId, string $event, array $context, string $now): void
    {
        $safe = array_diff_key($context, array_flip(['password', 'token', 'access_token', 'refresh_token']));
        $this->execute(
            'INSERT INTO audit_logs (actor_id, event, subject_type, subject_id, request_id, ip_address, after_json, created_at) '
            . 'VALUES (:actor, :event, :type, :subject, :request, :ip, :context, :now)',
            [
                'actor' => $actorId, 'event' => $event, 'type' => $safe['subject_type'] ?? null,
                'subject' => $safe['subject_id'] ?? null, 'request' => $safe['request_id'] ?? null,
                'ip' => $safe['ip_address'] ?? null, 'context' => json_encode($safe, JSON_THROW_ON_ERROR), 'now' => $now,
            ],
        );
    }
}
