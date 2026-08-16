<?php

namespace App\Domain\Invoicing;

class ProductCategory
{
    public ?int $id;
    public string $name;
    public int $domesticAccountId;
    public int $domesticVatRateId;
    public ?int $foreignAccountId;
    public ?int $foreignVatRateId;
    public bool $isActive;

    public function __construct(
        string $name,
        int $domesticAccountId,
        int $domesticVatRateId,
        ?int $foreignAccountId = null,
        ?int $foreignVatRateId = null,
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->domesticAccountId = $domesticAccountId;
        $this->domesticVatRateId = $domesticVatRateId;
        $this->foreignAccountId = $foreignAccountId;
        $this->foreignVatRateId = $foreignVatRateId;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            (int) $row['domestic_account_id'],
            (int) $row['domestic_vat_rate_id'],
            $row['foreign_account_id'] !== null ? (int) $row['foreign_account_id'] : null,
            $row['foreign_vat_rate_id'] !== null ? (int) $row['foreign_vat_rate_id'] : null,
            (bool) $row['is_active'],
            (int) $row['id']
        );
    }

    /** @return array{account_id: int, vat_rate_id: int} */
    public function resolveFor(bool $isForeignPartner): array
    {
        if ($isForeignPartner && $this->foreignAccountId && $this->foreignVatRateId) {
            return ['account_id' => $this->foreignAccountId, 'vat_rate_id' => $this->foreignVatRateId];
        }

        return ['account_id' => $this->domesticAccountId, 'vat_rate_id' => $this->domesticVatRateId];
    }
}
