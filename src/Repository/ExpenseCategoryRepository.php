<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\ExpenseCategory;
use PDO;

class ExpenseCategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return ExpenseCategory[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM expense_categories ORDER BY name ASC');

        return array_map([ExpenseCategory::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return ExpenseCategory[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY name ASC');

        return array_map([ExpenseCategory::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?ExpenseCategory
    {
        $stmt = $this->db->prepare('SELECT * FROM expense_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? ExpenseCategory::fromRow($row) : null;
    }

    public function create(ExpenseCategory $category): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO expense_categories (name, domestic_account_id, foreign_account_id, vat_deductible, is_capitalizable, default_annual_rate, reverse_charge_applicable, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $category->name,
            $category->domesticAccountId,
            $category->foreignAccountId,
            $category->vatDeductible,
            $category->isCapitalizable ? 1 : 0,
            $category->defaultAnnualRate,
            $category->reverseChargeApplicable ? 1 : 0,
            $category->isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(ExpenseCategory $category): void
    {
        $stmt = $this->db->prepare(
            'UPDATE expense_categories SET name = ?, domestic_account_id = ?, foreign_account_id = ?, vat_deductible = ?, is_capitalizable = ?, default_annual_rate = ?, reverse_charge_applicable = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $category->name,
            $category->domesticAccountId,
            $category->foreignAccountId,
            $category->vatDeductible,
            $category->isCapitalizable ? 1 : 0,
            $category->defaultAnnualRate,
            $category->reverseChargeApplicable ? 1 : 0,
            $category->isActive ? 1 : 0,
            $category->id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM expense_categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasPurchaseLines(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM purchase_invoice_lines WHERE expense_category_id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
