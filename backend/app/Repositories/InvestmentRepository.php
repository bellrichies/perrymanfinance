<?php

declare(strict_types=1);

namespace PerrymanFinance\Repositories;

use DateTimeImmutable;
use DateTimeZone;

final class InvestmentRepository extends AbstractRepository
{
    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        return array_values($this->execute(
            'SELECT id,name,slug,description,position,created_at,updated_at FROM investment_categories ORDER BY position,name',
        )->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function category(int $id): ?array
    {
        $row = $this->execute('SELECT * FROM investment_categories WHERE id=:id LIMIT 1', ['id' => $id])->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function insertCategory(array $data): int
    {
        $this->execute(
            'INSERT INTO investment_categories (name,slug,description,position,created_at,updated_at) VALUES (:name,:slug,:description,:position,:now,:now)',
            $data,
        );
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateCategory(int $id, array $data): void
    {
        $this->execute(
            'UPDATE investment_categories SET name=:name,slug=:slug,description=:description,position=:position,updated_at=:now WHERE id=:id',
            [...$data, 'id' => $id],
        );
    }

    public function deleteCategory(int $id): void
    {
        $this->execute('DELETE FROM investment_categories WHERE id=:id', ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function opportunities(array $filters, bool $public): array
    {
        $where = ['i.deleted_at IS NULL'];
        $params = [];
        if ($public) {
            $where[] = "i.status='published'";
            $where[] = 'i.published_at IS NOT NULL';
            $where[] = 'i.published_at <= :now';
            $params['now'] = $this->now();
        } elseif (isset($filters['status'])) {
            $where[] = 'i.status=:status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['category'])) {
            $where[] = 'c.slug=:category';
            $params['category'] = $filters['category'];
        }
        if (isset($filters['risk'])) {
            $where[] = 'i.risk_classification=:risk';
            $params['risk'] = $filters['risk'];
        }
        if (isset($filters['featured'])) {
            $where[] = 'i.featured=:featured';
            $params['featured'] = $filters['featured'];
        }
        if (isset($filters['search'])) {
            $where[] = '(i.title LIKE :search OR i.short_description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        $clause = implode(' AND ', $where);
        $total = (int) $this->execute(
            "SELECT COUNT(*) FROM investment_opportunities i JOIN investment_categories c ON c.id=i.category_id WHERE {$clause}",
            $params,
        )->fetchColumn();
        $sort = $this->allowedIdentifier((string) $filters['sort'], ['published_at', 'title', 'created_at', 'updated_at']);
        $direction = $filters['direction'] === 'asc' ? 'ASC' : 'DESC';
        $limit = (int) $filters['per_page'];
        $offset = ((int) $filters['page'] - 1) * $limit;
        $sql = 'SELECT i.*,c.name AS category_name,c.slug AS category_slug,m.uuid AS cover_media_uuid,m.path AS cover_media_path,m.alt_text AS cover_media_alt '
            . 'FROM investment_opportunities i JOIN investment_categories c ON c.id=i.category_id '
            . 'LEFT JOIN media_assets m ON m.id=i.cover_media_id AND m.deleted_at IS NULL '
            . "WHERE {$clause} ORDER BY i.{$sort} {$direction},i.id DESC LIMIT {$limit} OFFSET {$offset}";
        return ['items' => array_values($this->execute($sql, $params)->fetchAll()), 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function opportunity(string $key, bool $public = false): ?array
    {
        $where = $public
            ? "i.slug=:key AND i.status='published' AND i.published_at IS NOT NULL AND i.published_at<=:now"
            : 'i.uuid=:key';
        $params = $public ? ['key' => $key, 'now' => $this->now()] : ['key' => $key];
        $row = $this->execute(
            'SELECT i.*,c.name AS category_name,c.slug AS category_slug,m.uuid AS cover_media_uuid,m.path AS cover_media_path,m.alt_text AS cover_media_alt '
            . 'FROM investment_opportunities i JOIN investment_categories c ON c.id=i.category_id '
            . 'LEFT JOIN media_assets m ON m.id=i.cover_media_id AND m.deleted_at IS NULL '
            . "WHERE {$where} AND i.deleted_at IS NULL LIMIT 1",
            $params,
        )->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function insertOpportunity(array $data): int
    {
        $this->execute(
            'INSERT INTO investment_opportunities (uuid,category_id,title,slug,short_description,full_description,strategy_summary,investment_objective,investment_horizon,risk_classification,minimum_investment_display,currency_display,status,featured,cover_media_id,disclaimer,published_at,created_by,updated_by,created_at,updated_at) '
            . 'VALUES (:uuid,:category,:title,:slug,:short,:full,:strategy,:objective,:horizon,:risk,:minimum,:currency,:status,:featured,:cover,:disclaimer,:published,:actor,:actor,:now,:now)',
            $data,
        );
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateOpportunity(int $id, array $data): void
    {
        unset($data['uuid']);
        $this->execute(
            'UPDATE investment_opportunities SET category_id=:category,title=:title,slug=:slug,short_description=:short,full_description=:full,strategy_summary=:strategy,investment_objective=:objective,investment_horizon=:horizon,risk_classification=:risk,minimum_investment_display=:minimum,currency_display=:currency,status=:status,featured=:featured,cover_media_id=:cover,disclaimer=:disclaimer,published_at=:published,updated_by=:actor,updated_at=:now WHERE id=:id',
            [...$data, 'id' => $id],
        );
    }

    public function archiveOpportunity(int $id, int $actor, string $now): void
    {
        $this->execute("UPDATE investment_opportunities SET status='archived',updated_by=:actor,updated_at=:now WHERE id=:id", ['id' => $id, 'actor' => $actor, 'now' => $now]);
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }
}
