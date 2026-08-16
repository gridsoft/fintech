<?php

namespace App\Domain\Accounting;

class Account
{
    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public const TYPE_LABELS = [
        'asset' => 'Средство',
        'liability' => 'Обврска',
        'equity' => 'Капитал',
        'revenue' => 'Приход',
        'expense' => 'Расход',
    ];

    public ?int $id;
    public string $code;
    public string $name;
    public string $type;
    public ?int $parentId;
    public bool $isActive;

    public function __construct(
        string $code,
        string $name,
        string $type,
        ?int $parentId = null,
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->parentId = $parentId;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['code'],
            $row['name'],
            $row['type'],
            $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            (bool) $row['is_active'],
            (int) $row['id']
        );
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
