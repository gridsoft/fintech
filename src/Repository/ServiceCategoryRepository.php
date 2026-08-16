<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\ServiceCategory;
use PDO;

class ServiceCategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return ServiceCategory[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM service_categories ORDER BY name ASC');

        return array_map([ServiceCategory::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return ServiceCategory[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name ASC');

        return array_map([ServiceCategory::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?ServiceCategory
    {
        $stmt = $this->db->prepare('SELECT * FROM service_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? ServiceCategory::fromRow($row) : null;
    }

    public function create(ServiceCategory $category): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO service_categories (name, domestic_account_id, domestic_vat_rate_id, foreign_account_id, foreign_vat_rate_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $category->name,
            $category->domesticAccountId,
            $category->domesticVatRateId,
            $category->foreignAccountId,
            $category->foreignVatRateId,
            $category->isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(ServiceCategory $category): void
    {
        $stmt = $this->db->prepare(
            'UPDATE service_categories SET name = ?, domestic_account_id = ?, domestic_vat_rate_id = ?, foreign_account_id = ?, foreign_vat_rate_id = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $category->name,
            $category->domesticAccountId,
            $category->domesticVatRateId,
            $category->foreignAccountId,
            $category->foreignVatRateId,
            $category->isActive ? 1 : 0,
            $category->id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM service_categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasServices(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM services WHERE category_id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
