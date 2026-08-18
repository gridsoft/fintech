<?php

namespace App\Domain\Accounting;

class FxRevaluation
{
    public ?int $id;
    public string $date;
    public int $currencyId;
    public string $newRate;
    public ?int $journalEntryId;

    public function __construct(
        string $date,
        int $currencyId,
        string $newRate,
        ?int $journalEntryId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->date = $date;
        $this->currencyId = $currencyId;
        $this->newRate = $newRate;
        $this->journalEntryId = $journalEntryId;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['revaluation_date'],
            (int) $row['currency_id'],
            $row['new_rate'],
            $row['journal_entry_id'] !== null ? (int) $row['journal_entry_id'] : null,
            (int) $row['id']
        );
    }
}
