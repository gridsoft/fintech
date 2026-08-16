<?php

namespace App\Domain\Accounting;

class Nalog
{
    public ?int $id;
    public string $name;
    public int $terkId;
    public bool $isActive;

    public function __construct(string $name, int $terkId, bool $isActive = true, ?int $id = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->terkId = $terkId;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self($row['name'], (int) $row['terk_id'], (bool) $row['is_active'], (int) $row['id']);
    }
}
