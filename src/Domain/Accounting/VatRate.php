<?php

namespace App\Domain\Accounting;

class VatRate
{
    public const TYPES = [
        'standard',
        'reduced',
        'zero',
        'exempt_with_credit',
        'exempt_no_credit',
        'out_of_scope',
        'reverse_charge',
    ];

    public const TYPE_LABELS = [
        'standard' => 'Стандардна',
        'reduced' => 'Намалена',
        'zero' => 'Нулта стапка',
        'exempt_with_credit' => 'Ослободување со право на одбивка',
        'exempt_no_credit' => 'Ослободување без право на одбивка',
        'out_of_scope' => 'Надвор од опфат на ДДВ',
        'reverse_charge' => 'Обратно оданочување',
    ];

    public ?int $id;
    public string $name;
    public string $rate;
    public string $type;
    public ?int $payableAccountId;
    public ?int $receivableAccountId;
    public bool $isActive;

    public function __construct(
        string $name,
        string $rate,
        string $type,
        ?int $payableAccountId = null,
        ?int $receivableAccountId = null,
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->rate = $rate;
        $this->type = $type;
        $this->payableAccountId = $payableAccountId;
        $this->receivableAccountId = $receivableAccountId;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            $row['rate'],
            $row['type'],
            $row['payable_account_id'] !== null ? (int) $row['payable_account_id'] : null,
            $row['receivable_account_id'] !== null ? (int) $row['receivable_account_id'] : null,
            (bool) $row['is_active'],
            (int) $row['id']
        );
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
