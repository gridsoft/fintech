<?php

namespace App\Domain\Accounting;

class AdvanceApplication
{
    public ?int $id;
    public int $bankTransactionId;
    public string $invoiceType;
    public int $invoiceId;
    public string $amount;
    public int $journalEntryId;
    public string $appliedDate;

    public function __construct(
        int $bankTransactionId,
        string $invoiceType,
        int $invoiceId,
        string $amount,
        int $journalEntryId,
        string $appliedDate,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->bankTransactionId = $bankTransactionId;
        $this->invoiceType = $invoiceType;
        $this->invoiceId = $invoiceId;
        $this->amount = $amount;
        $this->journalEntryId = $journalEntryId;
        $this->appliedDate = $appliedDate;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['bank_transaction_id'],
            $row['invoice_type'],
            (int) $row['invoice_id'],
            $row['amount'],
            (int) $row['journal_entry_id'],
            $row['applied_date'],
            (int) $row['id']
        );
    }
}
