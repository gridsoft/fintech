<?php

namespace App\Domain\Accounting;

class Terk
{
    public ?int $id;
    public string $name;
    public ?string $description;
    public bool $isActive;

    /** @var TerkLine[] */
    public array $lines = [];

    public function __construct(string $name, ?string $description = null, bool $isActive = true, ?int $id = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self($row['name'], $row['description'], (bool) $row['is_active'], (int) $row['id']);
    }
}
