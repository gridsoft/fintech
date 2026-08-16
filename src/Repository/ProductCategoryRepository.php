<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\ProductCategory;
use PDO;

class ProductCategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return ProductCategory[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM product_categories ORDER BY name ASC');

        return array_map([ProductCategory::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return ProductCategory[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM product_categories WHERE is_active = 1 ORDER BY name ASC');

        return array_map([ProductCategory::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?ProductCategory
    {
        $stmt = $this->db->prepare('SELECT * FROM product_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? ProductCategory::fromRow($row) : null;
    }

    public function create(ProductCategory $category): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_categories (name, domestic_account_id, domestic_vat_rate_id, foreign_account_id, foreign_vat_rate_id, is_active)
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

    public function update(ProductCategory $category): void
    {
        $stmt = $this->db->prepare(
            'UPDATE product_categories SET name = ?, domestic_account_id = ?, domestic_vat_rate_id = ?, foreign_account_id = ?, foreign_vat_rate_id = ?, is_active = ?
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
        $stmt = $this->db->prepare('DELETE FROM product_categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasProducts(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
