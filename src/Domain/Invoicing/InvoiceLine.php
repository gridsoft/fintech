<?php

namespace App\Domain\Invoicing;

class InvoiceLine
{
    public ?int $id;
    public ?int $invoiceId;
    public string $description;
    public string $quantity;
    public string $unitPrice;
    public string $vatRate;
    public string $lineTotal;

    public function __construct(
        string $description,
        string $quantity,
        string $unitPrice,
        string $vatRate,
        string $lineTotal,
        ?int $invoiceId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->invoiceId = $invoiceId;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->vatRate = $vatRate;
        $this->lineTotal = $lineTotal;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['description'],
            $row['quantity'],
            $row['unit_price'],
            $row['vat_rate'],
            $row['line_total'],
            (int) $row['invoice_id'],
            (int) $row['id']
        );
    }

    public function vatAmount(): float
    {
        return round(((float) $this->lineTotal) * ((float) $this->vatRate) / 100, 2);
    }
}
