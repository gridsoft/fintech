<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use PDO;

class VatRateRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return VatRate[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM vat_rates ORDER BY rate DESC');

        return array_map([VatRate::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return VatRate[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM vat_rates WHERE is_active = 1 ORDER BY rate DESC');

        return array_map([VatRate::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?VatRate
    {
        $stmt = $this->db->prepare('SELECT * FROM vat_rates WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? VatRate::fromRow($row) : null;
    }

    public function create(VatRate $vatRate): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vat_rates (name, rate, type, payable_account_id, receivable_account_id, is_active) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vatRate->name,
            $vatRate->rate,
            $vatRate->type,
            $vatRate->payableAccountId,
            $vatRate->receivableAccountId,
            $vatRate->isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(VatRate $vatRate): void
    {
        $stmt = $this->db->prepare(
            'UPDATE vat_rates SET name = ?, rate = ?, type = ?, payable_account_id = ?, receivable_account_id = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $vatRate->name,
            $vatRate->rate,
            $vatRate->type,
            $vatRate->payableAccountId,
            $vatRate->receivableAccountId,
            $vatRate->isActive ? 1 : 0,
            $vatRate->id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vat_rates WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function isInUse(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM product_categories WHERE domestic_vat_rate_id = ? OR foreign_vat_rate_id = ?) +
                (SELECT COUNT(*) FROM service_categories WHERE domestic_vat_rate_id = ? OR foreign_vat_rate_id = ?) +
                (SELECT COUNT(*) FROM invoice_lines WHERE vat_rate_id = ?) +
                (SELECT COUNT(*) FROM purchase_invoice_lines WHERE vat_rate_id = ?)'
        );
        $stmt->execute([$id, $id, $id, $id, $id, $id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
