<?php

namespace App\Domain\Invoicing;

class Invoice
{
    public const STATUSES = ['draft', 'issued', 'paid', 'cancelled'];

    public const STATUS_LABELS = [
        'draft' => 'Нацрт',
        'issued' => 'Издадена',
        'paid' => 'Платена',
        'cancelled' => 'Откажана',
    ];

    public ?int $id;
    public int $partnerId;
    public string $number;
    public string $date;
    public string $dueDate;
    public string $status;
    public int $currencyId;
    public string $exchangeRate;
    public string $totalNet;
    public string $totalVat;
    public string $totalGross;
    public ?int $journalEntryId;

    /** @var InvoiceLine[] */
    public array $lines = [];

    public function __construct(
        int $partnerId,
        string $number,
        string $date,
        string $dueDate,
        string $status = 'draft',
        string $totalNet = '0.00',
        string $totalVat = '0.00',
        string $totalGross = '0.00',
        ?int $journalEntryId = null,
        ?int $id = null,
        int $currencyId = 1,
        string $exchangeRate = '1.000000'
    ) {
        $this->id = $id;
        $this->partnerId = $partnerId;
        $this->number = $number;
        $this->date = $date;
        $this->dueDate = $dueDate;
        $this->status = $status;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->totalNet = $totalNet;
        $this->totalVat = $totalVat;
        $this->totalGross = $totalGross;
        $this->journalEntryId = $journalEntryId;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['partner_id'],
            $row['number'],
            $row['invoice_date'],
            $row['due_date'],
            $row['status'],
            $row['total_net'],
            $row['total_vat'],
            $row['total_gross'],
            $row['journal_entry_id'] !== null ? (int) $row['journal_entry_id'] : null,
            (int) $row['id'],
            (int) $row['currency_id'],
            $row['exchange_rate']
        );
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** Бруто вкупно во MKD (базна валута) — валутата на документот × курсот замрзнат на фактурата. За денарски фактури курсот е 1.000000, нема ефект. */
    public function grossInBaseCurrency(): string
    {
        return bcmul($this->totalGross, $this->exchangeRate, 2);
    }
}
