<?php

namespace App\Domain\Accounting;

class JournalEntry
{
    public ?int $id;
    public string $date;
    public string $description;
    public ?string $reference;

    /** @var JournalLine[] */
    public array $lines = [];

    /** Вкупен дебит на записот — пополнето само кога доаѓа од allWithTotals(). */
    public ?string $total = null;

    public function __construct(string $date, string $description, ?string $reference = null, ?int $id = null)
    {
        $this->id = $id;
        $this->date = $date;
        $this->description = $description;
        $this->reference = $reference;
    }

    public static function fromRow(array $row): self
    {
        return new self($row['entry_date'], $row['description'], $row['reference'], (int) $row['id']);
    }
}
