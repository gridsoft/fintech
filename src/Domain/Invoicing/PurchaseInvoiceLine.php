<?php

namespace App\Domain\Invoicing;

class PurchaseInvoiceLine
{
    public ?int $id;
    public ?int $purchaseInvoiceId;
    public int $expenseCategoryId;
    public ?string $description;
    public string $quantity;
    public string $unitPrice;
    public int $accountId;
    public int $vatRateId;
    public string $vatRate;
    public string $lineTotal;

    public function __construct(
        int $expenseCategoryId,
        string $quantity,
        string $unitPrice,
        int $accountId,
        int $vatRateId,
        string $vatRate,
        string $lineTotal,
        ?string $description = null,
        ?int $purchaseInvoiceId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->purchaseInvoiceId = $purchaseInvoiceId;
        $this->expenseCategoryId = $expenseCategoryId;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->accountId = $accountId;
        $this->vatRateId = $vatRateId;
        $this->vatRate = $vatRate;
        $this->lineTotal = $lineTotal;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['expense_category_id'],
            $row['quantity'],
            $row['unit_price'],
            (int) $row['account_id'],
            (int) $row['vat_rate_id'],
            $row['vat_rate'],
            $row['line_total'],
            $row['description'],
            (int) $row['purchase_invoice_id'],
            (int) $row['id']
        );
    }

    public function vatAmount(): float
    {
        return round(((float) $this->lineTotal) * ((float) $this->vatRate) / 100, 2);
    }
}
