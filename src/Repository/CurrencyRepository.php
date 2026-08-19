<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\Currency;
use PDO;
use RuntimeException;

class CurrencyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Currency[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM currencies ORDER BY is_base DESC, code ASC');

        return array_map([Currency::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return Currency[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM currencies WHERE is_active = 1 ORDER BY is_base DESC, code ASC');

        return array_map([Currency::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Currency
    {
        $stmt = $this->db->prepare('SELECT * FROM currencies WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Currency::fromRow($row) : null;
    }

    /** Базната (денарска) валута — секогаш постои, внесена во migration 019. */
    public function base(): Currency
    {
        $stmt = $this->db->query('SELECT * FROM currencies WHERE is_base = 1 LIMIT 1');
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Не постои базна валута во системот.');
        }

        return Currency::fromRow($row);
    }

    public function create(Currency $currency): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO currencies (code, name, is_base, is_active, default_exchange_rate) VALUES (?, ?, 0, ?, ?)'
        );
        $stmt->execute([
            $currency->code,
            $currency->name,
            $currency->isActive ? 1 : 0,
            $currency->defaultExchangeRate,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Кодот и is_base намерно не се уредливи по создавање — базната валута е фиксна (migration 019). */
    public function update(Currency $currency): void
    {
        $stmt = $this->db->prepare('UPDATE currencies SET name = ?, is_active = ?, default_exchange_rate = ? WHERE id = ? AND is_base = 0');
        $stmt->execute([
            $currency->name,
            $currency->isActive ? 1 : 0,
            $currency->defaultExchangeRate,
            $currency->id,
        ]);
    }

    /** `AND is_base = 0` е дополнителна заштита — контролерот веќе одбива да ја избрише базната валута пред да стигне тука. */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM currencies WHERE id = ? AND is_base = 0');
        $stmt->execute([$id]);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM currencies WHERE code = ?';
        $params = [$code];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function isInUse(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM invoices WHERE currency_id = ?) +
                (SELECT COUNT(*) FROM purchase_invoices WHERE currency_id = ?)'
        );
        $stmt->execute([$id, $id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
