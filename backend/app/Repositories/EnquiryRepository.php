<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class EnquiryRepository extends AbstractRepository
{
    /** @return array{items:list<array<string,mixed>>,total:int} */
    public function listing(string $search, string $status, int $page, int $perPage): array
    {
        $where = 'deleted_at IS NULL';
        $parameters = [];
        if ($search !== '') {
            $where .= ' AND (name LIKE :name OR email LIKE :email OR subject LIKE :subject)';
            $parameters = ['name' => "%{$search}%", 'email' => "%{$search}%", 'subject' => "%{$search}%"];
        }
        if ($status !== '') {
            $where .= ' AND status = :status';
            $parameters['status'] = $status;
        }
        $total = (int) $this->execute("SELECT COUNT(*) FROM enquiries WHERE {$where}", $parameters)->fetchColumn();
        $offset = ($page - 1) * $perPage;
        $statement = $this->execute("SELECT uuid,name,email,enquiry_type,subject,status,created_at FROM enquiries WHERE {$where} ORDER BY created_at DESC,id DESC LIMIT {$perPage} OFFSET {$offset}", $parameters);
        $items = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = ['uuid' => $row['uuid'], 'name' => $row['name'], 'email' => $row['email'], 'enquiry_type' => $row['enquiry_type'], 'subject' => $row['subject'], 'status' => $row['status'], 'created_at' => $row['created_at']];
        }
        return ['items' => $items, 'total' => $total];
    }

    /** @return array<string,mixed>|null */
    public function find(string $uuid): ?array
    {
        $row = $this->execute('SELECT * FROM enquiries WHERE uuid=:uuid AND deleted_at IS NULL', ['uuid' => $uuid])->fetch();
        return $row === false ? null : $row;
    }

    public function changeStatus(string $uuid, string $previous, string $status, string $now): bool
    {
        return $this->execute('UPDATE enquiries SET status=:status, resolved_at=:resolved, updated_at=:now WHERE uuid=:uuid AND status=:previous AND deleted_at IS NULL', [
            'status' => $status, 'resolved' => $status === 'resolved' ? $now : null,
            'now' => $now, 'uuid' => $uuid, 'previous' => $previous,
        ])->rowCount() === 1;
    }

    /** @param array<string, mixed> $record */
    public function create(array $record): void
    {
        $this->execute(
            'INSERT INTO enquiries (uuid,name,email,phone,enquiry_type,subject,message,source_page,consent_at,status,created_at,updated_at) '
            . "VALUES (:uuid,:name,:email,:phone,:enquiry_type,:subject,:message,:source_page,:consent_at,'new',:created_at,:updated_at)",
            $record,
        );
    }
}
