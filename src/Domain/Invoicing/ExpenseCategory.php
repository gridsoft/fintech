<?php

namespace App\Domain\Invoicing;

class ExpenseCategory
{
    public const VAT_DEDUCTIBLE_OPTIONS = ['full', 'none', 'partial'];

    public const VAT_DEDUCTIBLE_LABELS = [
        'full' => 'Целосно одбивен',
        'none' => 'Неодбивен',
        'partial' => 'Делумно одбивен',
    ];

    public ?int $id;
    public string $name;
    public int $domesticAccountId;
    public ?int $foreignAccountId;
    public string $vatDeductible;
    public bool $isCapitalizable;
    public bool $reverseChargeApplicable;
    public bool $isActive;

    public function __construct(
        string $name,
        int $domesticAccountId,
        ?int $foreignAccountId = null,
        string $vatDeductible = 'full',
        bool $isCapitalizable = false,
        bool $reverseChargeApplicable = false,
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->domesticAccountId = $domesticAccountId;
        $this->foreignAccountId = $foreignAccountId;
        $this->vatDeductible = $vatDeductible;
        $this->isCapitalizable = $isCapitalizable;
        $this->reverseChargeApplicable = $reverseChargeApplicable;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            (int) $row['domestic_account_id'],
            $row['foreign_account_id'] !== null ? (int) $row['foreign_account_id'] : null,
            $row['vat_deductible'],
            (bool) $row['is_capitalizable'],
            (bool) $row['reverse_charge_applicable'],
            (bool) $row['is_active'],
            (int) $row['id']
        );
    }

    public function vatDeductibleLabel(): string
    {
        return self::VAT_DEDUCTIBLE_LABELS[$this->vatDeductible] ?? $this->vatDeductible;
    }

    public function resolveAccountFor(bool $isForeignPartner): int
    {
        if ($isForeignPartner && $this->foreignAccountId) {
            return $this->foreignAccountId;
        }

        return $this->domesticAccountId;
    }
}
