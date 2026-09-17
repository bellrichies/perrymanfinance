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

    /** @return list<array<string, mixed>> */
    public function latest(int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        return array_values($this->execute(
            "SELECT a.id,a.event,a.subject_type,a.subject_id,a.request_id,a.ip_address,a.created_at,"
            . "u.email AS actor_email,u.display_name AS actor_name "
            . "FROM audit_logs a LEFT JOIN admin_users u ON u.id=a.actor_id "
            . "ORDER BY a.created_at DESC,a.id DESC LIMIT {$limit}",
        )->fetchAll());
    }
}
