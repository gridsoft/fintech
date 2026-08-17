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
    public ?string $addressLine1;
    public ?string $addressLine2;
    public ?string $postalCode;
    public ?string $city;
    public string $country;
    public ?string $phone;
    public ?string $fax;
    public ?string $mobile;
    public ?string $email;
    public ?string $website;
    public ?string $bankAccount;
    public ?string $vatNumber;
    public ?string $iban;
    public ?string $swift;
    public ?string $timocomId;
    public bool $isActive;

    public function __construct(
        string $name,
        string $type,
        ?string $taxNumber = null,
        ?string $addressLine1 = null,
        ?string $addressLine2 = null,
        ?string $postalCode = null,
        ?string $city = null,
        string $country = 'MK',
        ?string $phone = null,
        ?string $fax = null,
        ?string $mobile = null,
        ?string $email = null,
        ?string $website = null,
        ?string $bankAccount = null,
        ?string $vatNumber = null,
        ?string $iban = null,
        ?string $swift = null,
        ?string $timocomId = null,
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->taxNumber = $taxNumber;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->country = $country;
        $this->phone = $phone;
        $this->fax = $fax;
        $this->mobile = $mobile;
        $this->email = $email;
        $this->website = $website;
        $this->bankAccount = $bankAccount;
        $this->vatNumber = $vatNumber;
        $this->iban = $iban;
        $this->swift = $swift;
        $this->timocomId = $timocomId;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            $row['type'],
            $row['tax_number'],
            $row['address_line1'],
            $row['address_line2'],
            $row['postal_code'],
            $row['city'],
            $row['country'] ?? 'MK',
            $row['phone'],
            $row['fax'],
            $row['mobile'],
            $row['email'],
            $row['website'],
            $row['bank_account'],
            $row['vat_number'],
            $row['iban'],
            $row['swift'],
            $row['timocom_id'],
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

    /** Едноредна адреса за компактен приказ (листи, фактури). */
    public function addressSummary(): ?string
    {
        $parts = array_filter([
            $this->addressLine1,
            $this->addressLine2,
            trim(($this->postalCode ?? '') . ' ' . ($this->city ?? '')),
        ], fn ($part) => trim((string) $part) !== '');

        return $parts ? implode(', ', $parts) : null;
    }
}
