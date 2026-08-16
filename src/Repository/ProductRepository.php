<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\Product;
use PDO;

class ProductRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Product[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM products ORDER BY name ASC');

        return array_map([Product::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return Product[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC');

        return array_map([Product::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Product
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Product::fromRow($row) : null;
    }

    public function create(Product $product): int
    {
        $stmt = $this->db->prepare('INSERT INTO products (name, category_id, price, is_active) VALUES (?, ?, ?, ?)');
        $stmt->execute([$product->name, $product->categoryId, $product->price, $product->isActive ? 1 : 0]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Product $product): void
    {
        $stmt = $this->db->prepare('UPDATE products SET name = ?, category_id = ?, price = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$product->name, $product->categoryId, $product->price, $product->isActive ? 1 : 0, $product->id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
    }
}
