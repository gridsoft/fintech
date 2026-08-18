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
    public int $usefulLifeMonths;
    public string $status;

    public function __construct(
        string $name,
        int $accountId,
        int $purchaseInvoiceId,
        int $purchaseInvoiceLineId,
        string $purchaseDate,
        string $purchaseValue,
        int $usefulLifeMonths,
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
        $this->usefulLifeMonths = $usefulLifeMonths;
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
            (int) $row['useful_life_months'],
            $row['status'],
            (int) $row['id']
        );
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function monthlyDepreciation(): string
    {
        return bcdiv($this->purchaseValue, (string) $this->usefulLifeMonths, 2);
    }
}
