<?php

namespace App\Domain\Accounting;

class FixedAsset
{
    public const STATUSES = ['active', 'disposed'];

    public const STATUS_LABELS = [
        'active' => 'активно',
        'disposed' => 'отуѓено',
    ];

    public ?int $id;
    public string $name;
    public int $accountId;
    public int $purchaseInvoiceId;
    public int $purchaseInvoiceLineId;
    public string $purchaseDate;
    public string $purchaseValue;
    public string $annualRate;
    public string $status;

    public function __construct(
        string $name,
        int $accountId,
        int $purchaseInvoiceId,
        int $purchaseInvoiceLineId,
        string $purchaseDate,
        string $purchaseValue,
        string $annualRate,
        string $status = 'active',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->accountId = $accountId;
        $this->purchaseInvoiceId = $purchaseInvoiceId;
        $this->purchaseInvoiceLineId = $purchaseInvoiceLineId;
        $this->purchaseDate = $purchaseDate;
        $this->purchaseValue = $purchaseValue;
        $this->annualRate = $annualRate;
        $this->status = $status;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            (int) $row['account_id'],
            (int) $row['purchase_invoice_id'],
            (int) $row['purchase_invoice_line_id'],
            $row['purchase_date'],
            $row['purchase_value'],
            $row['annual_rate'],
            $row['status'],
            (int) $row['id']
        );
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** Месечна амортизација = набавна вредност * годишна стапка (%) / 12. */
    public function monthlyDepreciation(): string
    {
        $yearly = bcmul($this->purchaseValue, bcdiv($this->annualRate, '100', 6), 2);

        return bcdiv($yearly, '12', 2);
    }
}
