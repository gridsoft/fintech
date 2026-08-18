<?php

namespace App\Domain\Accounting;

class FixedAssetDepreciationEntry
{
    public ?int $id;
    public int $fixedAssetId;
    public string $periodDate;
    public string $amount;
    public int $journalEntryId;

    public function __construct(
        int $fixedAssetId,
        string $periodDate,
        string $amount,
        int $journalEntryId,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->fixedAssetId = $fixedAssetId;
        $this->periodDate = $periodDate;
        $this->amount = $amount;
        $this->journalEntryId = $journalEntryId;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['fixed_asset_id'],
            $row['period_date'],
            $row['amount'],
            (int) $row['journal_entry_id'],
            (int) $row['id']
        );
    }
}
