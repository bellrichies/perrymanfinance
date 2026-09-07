<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

final class MediaRepository extends AbstractRepository
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return array_values($this->execute('SELECT * FROM media_assets WHERE deleted_at IS NULL ORDER BY created_at DESC')->fetchAll());
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->execute(
            'INSERT INTO media_assets (uuid,disk,path,original_name,mime_type,byte_size,width,height,alt_text,uploaded_by,created_at,updated_at) '
            . 'VALUES (:uuid,:disk,:path,:name,:mime,:size,:width,:height,:alt,:actor,:now,:now)',
            $data,
        );
    }

    /** @return array<string, mixed>|null */
    public function find(string $uuid): ?array
    {
        $row = $this->execute('SELECT * FROM media_assets WHERE uuid=:uuid AND deleted_at IS NULL LIMIT 1', ['uuid' => $uuid])->fetch();
        return is_array($row) ? $row : null;
    }

    public function delete(string $uuid, string $now): void
    {
        $this->execute('UPDATE media_assets SET deleted_at=:now,updated_at=:now WHERE uuid=:uuid', ['uuid' => $uuid, 'now' => $now]);
    }

    public function updateAltText(string $uuid, ?string $alt, string $now): void
    {
        $this->execute(
            'UPDATE media_assets SET alt_text=:alt,updated_at=:now WHERE uuid=:uuid AND deleted_at IS NULL',
            ['alt' => $alt, 'now' => $now, 'uuid' => $uuid],
        );
    }
}
