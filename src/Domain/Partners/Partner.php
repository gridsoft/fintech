<?php

namespace App\Domain\Partners;

class Partner
{
    public const TYPES = ['customer', 'supplier', 'both'];

    public const TYPE_LABELS = [
        'customer' => 'Купувач',
        'supplier' => 'Добавувач',
        'both' => 'Купувач и добавувач',
    ];

    public ?int $id;
    public string $name;
    public string $type;
    public ?string $taxNumber;
    public ?string $address;
    public ?string $contact;
    public string $country;
    public bool $isActive;

    public function __construct(
        string $name,
        string $type,
        ?string $taxNumber = null,
        ?string $address = null,
        ?string $contact = null,
        string $country = 'MK',
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->taxNumber = $taxNumber;
        $this->address = $address;
        $this->contact = $contact;
        $this->country = $country;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            $row['type'],
            $row['tax_number'],
            $row['address'],
            $row['contact'],
            $row['country'] ?? 'MK',
            (bool) $row['is_active'],
            (int) $row['id']
        );
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function isForeign(): bool
    {
        return $this->country !== 'MK';
    }
}
