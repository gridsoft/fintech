<?php

namespace App\Domain\Invoicing;

class PurchaseInvoice
{
    public const STATUSES = ['draft', 'posted', 'paid', 'cancelled'];

    public const STATUS_LABELS = [
        'draft' => 'Нацрт',
        'posted' => 'Заведена',
        'paid' => 'Платена',
        'cancelled' => 'Откажана',
    ];

    public ?int $id;
    public int $partnerId;
    public string $supplierNumber;
    public string $date;
    public string $dueDate;
    public string $status;
    public string $totalNet;
    public string $totalVat;
    public string $totalGross;
    public ?int $journalEntryId;

    /** @var PurchaseInvoiceLine[] */
    public array $lines = [];

    public function __construct(
        int $partnerId,
        string $supplierNumber,
        string $date,
        string $dueDate,
        string $status = 'draft',
        string $totalNet = '0.00',
        string $totalVat = '0.00',
        string $totalGross = '0.00',
        ?int $journalEntryId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->partnerId = $partnerId;
        $this->supplierNumber = $supplierNumber;
        $this->date = $date;
        $this->dueDate = $dueDate;
        $this->status = $status;
        $this->totalNet = $totalNet;
        $this->totalVat = $totalVat;
        $this->totalGross = $totalGross;
        $this->journalEntryId = $journalEntryId;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['partner_id'],
            $row['supplier_number'],
            $row['invoice_date'],
            $row['due_date'],
            $row['status'],
            $row['total_net'],
            $row['total_vat'],
            $row['total_gross'],
            $row['journal_entry_id'] !== null ? (int) $row['journal_entry_id'] : null,
            (int) $row['id']
        );
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
