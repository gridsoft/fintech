<?php

namespace App\Domain\Invoicing;

class Product
{
    public ?int $id;
    public string $name;
    public int $categoryId;
    public string $price;
    public bool $isActive;

    public function __construct(string $name, int $categoryId, string $price, bool $isActive = true, ?int $id = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->categoryId = $categoryId;
        $this->price = $price;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self($row['name'], (int) $row['category_id'], $row['price'], (bool) $row['is_active'], (int) $row['id']);
    }
}
