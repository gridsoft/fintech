<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\FixedAsset;
use App\Domain\Accounting\FixedAssetDepreciationEntry;
use PDO;

class FixedAssetRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return FixedAsset[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM fixed_assets ORDER BY purchase_date DESC, id DESC');

        return array_map([FixedAsset::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return FixedAsset[] */
    public function allActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM fixed_assets WHERE status = 'active' ORDER BY purchase_date ASC, id ASC");

        return array_map([FixedAsset::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?FixedAsset
    {
        $stmt = $this->db->prepare('SELECT * FROM fixed_assets WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? FixedAsset::fromRow($row) : null;
    }

    public function create(FixedAsset $asset): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO fixed_assets (name, account_id, purchase_invoice_id, purchase_invoice_line_id, purchase_date, purchase_value, annual_rate, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $asset->name,
            $asset->accountId,
            $asset->purchaseInvoiceId,
            $asset->purchaseInvoiceLineId,
            $asset->purchaseDate,
            $asset->purchaseValue,
            $asset->annualRate,
            $asset->status,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Само име и стапка на амортизација се уредливи — вредноста/датумот/контото се замрзнати од изворната фактура. */
    public function update(FixedAsset $asset): void
    {
        $stmt = $this->db->prepare('UPDATE fixed_assets SET name = ?, annual_rate = ? WHERE id = ?');
        $stmt->execute([$asset->name, $asset->annualRate, $asset->id]);
    }

    /** Дали влезната фактура веќе создаде основно средство — уредување по ова е блокирано (вредноста е замрзната на средството). */
    public function existsForPurchaseInvoice(int $purchaseInvoiceId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM fixed_assets WHERE purchase_invoice_id = ?');
        $stmt->execute([$purchaseInvoiceId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Веќе амортизирано до сега — SUM од сите записи, нема посебно чувано поле (исто како преостанато салдо на фактура). */
    public function accumulatedDepreciation(int $fixedAssetId): string
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) FROM fixed_asset_depreciation_entries WHERE fixed_asset_id = ?');
        $stmt->execute([$fixedAssetId]);

        return (string) $stmt->fetchColumn();
    }

    public function hasDepreciationForPeriod(int $fixedAssetId, string $periodDate): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM fixed_asset_depreciation_entries WHERE fixed_asset_id = ? AND period_date = ?');
        $stmt->execute([$fixedAssetId, $periodDate]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return FixedAssetDepreciationEntry[] */
    public function depreciationEntriesForAsset(int $fixedAssetId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM fixed_asset_depreciation_entries WHERE fixed_asset_id = ? ORDER BY period_date ASC, id ASC');
        $stmt->execute([$fixedAssetId]);

        return array_map([FixedAssetDepreciationEntry::class, 'fromRow'], $stmt->fetchAll());
    }

    public function insertDepreciationEntry(FixedAssetDepreciationEntry $entry): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO fixed_asset_depreciation_entries (fixed_asset_id, period_date, amount, journal_entry_id)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $entry->fixedAssetId,
            $entry->periodDate,
            $entry->amount,
            $entry->journalEntryId,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
