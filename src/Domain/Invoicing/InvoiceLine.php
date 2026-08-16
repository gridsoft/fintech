<?php

namespace App\Domain\Invoicing;

class InvoiceLine
{
    public ?int $id;
    public ?int $invoiceId;
    public ?int $productId;
    public ?int $serviceId;
    public ?string $description;
    public string $quantity;
    public string $unitPrice;
    public int $accountId;
    public int $vatRateId;
    public string $vatRate;
    public string $lineTotal;

    public function __construct(
        string $quantity,
        string $unitPrice,
        int $accountId,
        int $vatRateId,
        string $vatRate,
        string $lineTotal,
        ?int $productId = null,
        ?int $serviceId = null,
        ?string $description = null,
        ?int $invoiceId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->invoiceId = $invoiceId;
        $this->productId = $productId;
        $this->serviceId = $serviceId;
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
            $row['quantity'],
            $row['unit_price'],
            (int) $row['account_id'],
            (int) $row['vat_rate_id'],
            $row['vat_rate'],
            $row['line_total'],
            $row['product_id'] !== null ? (int) $row['product_id'] : null,
            $row['service_id'] !== null ? (int) $row['service_id'] : null,
            $row['description'],
            (int) $row['invoice_id'],
            (int) $row['id']
        );
    }

    public function vatAmount(): float
    {
        return round(((float) $this->lineTotal) * ((float) $this->vatRate) / 100, 2);
    }
}
