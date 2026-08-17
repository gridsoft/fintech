<?php

namespace App\Domain\Accounting;

class BankTransaction
{
    public const DIRECTIONS = ['in', 'out'];

    public const DIRECTION_LABELS = [
        'in' => 'Уплата (влез)',
        'out' => 'Исплата (излез)',
    ];

    public const INVOICE_TYPE_LABELS = [
        'sales' => 'Излезна фактура',
        'purchase' => 'Влезна фактура',
    ];

    public ?int $id;
    public int $bankStatementId;
    public string $date;
    public ?string $description;
    public string $amount;
    public string $direction;
    public ?int $partnerId;
    public string $matchedStatus;
    public ?string $invoiceType;
    public ?int $invoiceId;
    public ?int $journalEntryId;

    /** Само пополнето кога редот е вчитан преку join со bank_statements. */
    public ?int $accountId = null;

    public function __construct(
        int $bankStatementId,
        string $date,
        string $amount,
        string $direction,
        ?string $description = null,
        ?int $partnerId = null,
        string $matchedStatus = 'unmatched',
        ?string $invoiceType = null,
        ?int $invoiceId = null,
        ?int $journalEntryId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->bankStatementId = $bankStatementId;
        $this->date = $date;
        $this->description = $description;
        $this->amount = $amount;
        $this->direction = $direction;
        $this->partnerId = $partnerId;
        $this->matchedStatus = $matchedStatus;
        $this->invoiceType = $invoiceType;
        $this->invoiceId = $invoiceId;
        $this->journalEntryId = $journalEntryId;
    }

    public static function fromRow(array $row): self
    {
        $transaction = new self(
            (int) $row['bank_statement_id'],
            $row['transaction_date'],
            $row['amount'],
            $row['direction'],
            $row['description'],
            $row['partner_id'] !== null ? (int) $row['partner_id'] : null,
            $row['matched_status'],
            $row['invoice_type'],
            $row['invoice_id'] !== null ? (int) $row['invoice_id'] : null,
            $row['journal_entry_id'] !== null ? (int) $row['journal_entry_id'] : null,
            (int) $row['id']
        );

        if (isset($row['account_id'])) {
            $transaction->accountId = (int) $row['account_id'];
        }

        return $transaction;
    }

    public function directionLabel(): string
    {
        return self::DIRECTION_LABELS[$this->direction] ?? $this->direction;
    }

    public function invoiceTypeLabel(): string
    {
        return self::INVOICE_TYPE_LABELS[$this->invoiceType] ?? '—';
    }
}
