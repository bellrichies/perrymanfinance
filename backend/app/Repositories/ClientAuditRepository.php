<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class ClientAuditRepository extends AbstractRepository
{
    /** @param array<string, mixed> $context */
    public function record(string $actorType, ?int $actorId, ?int $clientUserId, string $action, array $context, string $now): void
    {
        $this->execute(
            'INSERT INTO client_audit_events (actor_type,actor_id,client_user_id,action,entity_type,entity_id,new_values_json,ip_address,user_agent,request_id,created_at) VALUES (:actor_type,:actor_id,:client_user_id,:action,:entity_type,:entity_id,:new_values,:ip,:agent,:request_id,:created_at)',
            [
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'client_user_id' => $clientUserId,
                'action' => $action,
                'entity_type' => $context['entity_type'] ?? null,
                'entity_id' => $context['entity_id'] ?? null,
                'new_values' => isset($context['values']) ? json_encode($context['values'], JSON_THROW_ON_ERROR) : null,
                'ip' => $context['ip_address'] ?? null,
                'agent' => isset($context['user_agent']) ? substr((string) $context['user_agent'], 0, 255) : null,
                'request_id' => $context['request_id'] ?? null,
                'created_at' => $now,
            ],
        );
    }

    /** @return list<array<string, mixed>> */
    public function forClient(int $clientUserId): array
    {
        return array_values($this->execute(
            'SELECT actor_type,action,entity_type,entity_id,created_at FROM client_audit_events WHERE client_user_id=:id ORDER BY created_at DESC LIMIT 50',
            ['id' => $clientUserId],
        )->fetchAll());
    }
}
