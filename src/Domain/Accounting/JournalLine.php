<?php

namespace App\Domain\Accounting;

class JournalLine
{
    public ?int $id;
    public ?int $journalEntryId;
    public int $accountId;
    public ?int $partnerId;
    public string $debit;
    public string $credit;
    public ?string $description;

    public function __construct(
        int $accountId,
        string $debit,
        string $credit,
        ?string $description = null,
        ?int $journalEntryId = null,
        ?int $partnerId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->journalEntryId = $journalEntryId;
        $this->accountId = $accountId;
        $this->partnerId = $partnerId;
        $this->debit = $debit;
        $this->credit = $credit;
        $this->description = $description;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['account_id'],
            $row['debit'],
            $row['credit'],
            $row['description'],
            (int) $row['journal_entry_id'],
            $row['partner_id'] !== null ? (int) $row['partner_id'] : null,
            (int) $row['id']
        );
    }
}
