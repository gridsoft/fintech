<?php

namespace App\Domain\Accounting;

class Nalog
{
    public ?int $id;
    public string $name;
    public bool $isActive;

    public function __construct(string $name, bool $isActive = true, ?int $id = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self($row['name'], (bool) $row['is_active'], (int) $row['id']);
    }
}
