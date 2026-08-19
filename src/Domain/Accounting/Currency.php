<?php

namespace App\Domain\Accounting;

class Currency
{
    public ?int $id;
    public string $code;
    public string $name;
    public bool $isBase;
    public bool $isActive;
    public ?string $defaultExchangeRate;

    public function __construct(
        string $code,
        string $name,
        bool $isBase = false,
        bool $isActive = true,
        ?int $id = null,
        ?string $defaultExchangeRate = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->isBase = $isBase;
        $this->isActive = $isActive;
        $this->defaultExchangeRate = $defaultExchangeRate;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['code'],
            $row['name'],
            (bool) $row['is_base'],
            (bool) $row['is_active'],
            (int) $row['id'],
            $row['default_exchange_rate']
        );
    }
}
