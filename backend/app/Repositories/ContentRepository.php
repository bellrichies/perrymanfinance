<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

use PDO;

final class ContentRepository extends AbstractRepository
{
    /** @return list<array<string, mixed>> */
    public function pages(): array
    {
        return array_values($this->execute('SELECT * FROM pages WHERE deleted_at IS NULL ORDER BY updated_at DESC')->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function page(string $uuid, bool $public = false): ?array
    {
        $sql = 'SELECT * FROM pages WHERE ' . ($public ? 'slug = :key AND status = \'published\'' : 'uuid = :key')
            . ' AND deleted_at IS NULL LIMIT 1';
        $row = $this->execute($sql, ['key' => $uuid])->fetch();
        if (!is_array($row)) {
            return null;
        }
        $row['sections'] = $this->execute(
            'SELECT id, section_type AS type, position, content_json AS content FROM page_sections '
            . 'WHERE page_id = :id ORDER BY position',
            ['id' => $row['id']],
        )->fetchAll();
        return $this->decodePage($row);
    }

    /** @param array<string, mixed> $data */
    public function insertPage(array $data): int
    {
        $this->execute(
            'INSERT INTO pages (uuid,title,slug,page_type,status,excerpt,content_json,published_at,created_by,updated_by,created_at,updated_at) '
            . 'VALUES (:uuid,:title,:slug,:type,:status,:excerpt,:content,:published,:actor,:actor,:now,:now)',
            $data,
        );
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updatePage(int $id, array $data): void
    {
        unset($data['uuid']);
        $this->execute(
            'UPDATE pages SET title=:title,slug=:slug,page_type=:type,status=:status,excerpt=:excerpt,'
            . 'content_json=:content,published_at=:published,updated_by=:actor,updated_at=:now WHERE id=:id',
            [...$data, 'id' => $id],
        );
    }

    /** @param list<array<string, mixed>> $sections */
    public function replaceSections(int $pageId, array $sections, string $now): void
    {
        $this->execute('DELETE FROM page_sections WHERE page_id = :id', ['id' => $pageId]);
        foreach ($sections as $position => $section) {
            $this->execute(
                'INSERT INTO page_sections (page_id,section_type,position,content_json,created_at,updated_at) '
                . 'VALUES (:page,:type,:position,:content,:now,:now)',
                ['page' => $pageId, 'type' => $section['type'], 'position' => $position, 'content' => json_encode($section['content'], JSON_THROW_ON_ERROR), 'now' => $now],
            );
        }
    }

    public function deletePage(int $id, int $actor, string $now): void
    {
        $this->execute('UPDATE pages SET deleted_at=:now,updated_by=:actor,updated_at=:now WHERE id=:id', ['now' => $now, 'actor' => $actor, 'id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function legalDocuments(): array
    {
        return array_values($this->execute('SELECT * FROM legal_documents ORDER BY updated_at DESC')->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function legal(string $key, bool $public = false): ?array
    {
        $sql = $public
            ? "SELECT * FROM legal_documents WHERE slug=:key AND status='published' AND (effective_at IS NULL OR effective_at <= :now) ORDER BY effective_at DESC, published_at DESC LIMIT 1"
            : 'SELECT * FROM legal_documents WHERE uuid=:key LIMIT 1';
        $params = $public ? ['key' => $key, 'now' => gmdate('Y-m-d H:i:s.u')] : ['key' => $key];
        $row = $this->execute($sql, $params)->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function insertLegal(array $data): void
    {
        $this->execute(
            'INSERT INTO legal_documents (uuid,document_type,title,slug,version,content,effective_at,status,published_at,created_by,updated_by,created_at,updated_at) '
            . 'VALUES (:uuid,:type,:title,:slug,:version,:content,:effective,:status,:published,:actor,:actor,:now,:now)',
            $data,
        );
    }

    /** @param array<string, mixed> $data */
    public function updateLegal(int $id, array $data): void
    {
        unset($data['uuid']);
        $this->execute(
            'UPDATE legal_documents SET document_type=:type,title=:title,slug=:slug,version=:version,content=:content,'
            . 'effective_at=:effective,status=:status,published_at=:published,updated_by=:actor,updated_at=:now WHERE id=:id',
            [...$data, 'id' => $id],
        );
    }

    /** @return list<array<string, mixed>> */
    public function settings(bool $public = false): array
    {
        $rows = $this->execute('SELECT * FROM site_settings' . ($public ? ' WHERE is_public = 1' : '') . ' ORDER BY setting_key')->fetchAll();
        return array_values(array_map(static function (array $row): array {
            $row['value'] = json_decode((string) $row['value_json'], true);
            unset($row['value_json']);
            return $row;
        }, $rows));
    }

    public function upsertSetting(string $key, mixed $value, bool $public, int $actor, string $now): void
    {
        $existing = $this->execute('SELECT id FROM site_settings WHERE setting_key=:key', ['key' => $key])->fetchColumn();
        $params = ['key' => $key, 'value' => json_encode($value, JSON_THROW_ON_ERROR), 'public' => $public ? 1 : 0, 'actor' => $actor, 'now' => $now];
        if ($existing === false) {
            $this->execute('INSERT INTO site_settings (setting_key,value_json,is_public,updated_by,created_at,updated_at) VALUES (:key,:value,:public,:actor,:now,:now)', $params);
            return;
        }
        $this->execute('UPDATE site_settings SET value_json=:value,is_public=:public,updated_by=:actor,updated_at=:now WHERE setting_key=:key', $params);
    }

    public function deleteSetting(string $key): void
    {
        $this->execute('DELETE FROM site_settings WHERE setting_key=:key', ['key' => $key]);
    }

    /** @return array<string, mixed>|null */
    public function seo(string $type, int $ownerId): ?array
    {
        $column = $this->ownerColumn($type);
        $row = $this->execute("SELECT * FROM seo_metadata WHERE {$column}=:owner LIMIT 1", ['owner' => $ownerId])->fetch();
        return is_array($row) ? $this->decodeSeo($row) : null;
    }

    /** @param array<string, mixed> $data */
    public function upsertSeo(string $type, int $ownerId, array $data): void
    {
        $column = $this->ownerColumn($type);
        $existing = $this->seo($type, $ownerId);
        if ($existing === null) {
            $this->execute(
                "INSERT INTO seo_metadata ({$column},meta_title,meta_description,canonical_url,robots,open_graph_json,social_media_id,created_at,updated_at) VALUES (:owner,:title,:description,:canonical,:robots,:og,:social,:now,:now)",
                ['owner' => $ownerId, ...$data],
            );
            return;
        }
        $this->execute(
            'UPDATE seo_metadata SET meta_title=:title,meta_description=:description,canonical_url=:canonical,robots=:robots,open_graph_json=:og,social_media_id=:social,updated_at=:now WHERE id=:id',
            ['id' => $existing['id'], ...$data],
        );
    }

    public function deleteSeo(string $type, int $ownerId): void
    {
        $column = $this->ownerColumn($type);
        $this->execute("DELETE FROM seo_metadata WHERE {$column}=:owner", ['owner' => $ownerId]);
    }

    private function ownerColumn(string $type): string
    {
        return $this->allowedIdentifier($type . '_id', ['page_id', 'legal_document_id']);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decodePage(array $row): array
    {
        $row['content'] = $row['content_json'] === null ? null : json_decode((string) $row['content_json'], true);
        unset($row['content_json']);
        foreach ($row['sections'] as &$section) {
            $section['content'] = json_decode((string) $section['content'], true);
        }
        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decodeSeo(array $row): array
    {
        $row['open_graph'] = $row['open_graph_json'] === null ? null : json_decode((string) $row['open_graph_json'], true);
        unset($row['open_graph_json']);
        return $row;
    }
}
