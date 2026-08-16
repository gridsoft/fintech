<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\Service;
use PDO;

class ServiceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Service[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM services ORDER BY name ASC');

        return array_map([Service::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return Service[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM services WHERE is_active = 1 ORDER BY name ASC');

        return array_map([Service::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Service
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Service::fromRow($row) : null;
    }

    public function create(Service $service): int
    {
        $stmt = $this->db->prepare('INSERT INTO services (name, category_id, price, is_active) VALUES (?, ?, ?, ?)');
        $stmt->execute([$service->name, $service->categoryId, $service->price, $service->isActive ? 1 : 0]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Service $service): void
    {
        $stmt = $this->db->prepare('UPDATE services SET name = ?, category_id = ?, price = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$service->name, $service->categoryId, $service->price, $service->isActive ? 1 : 0, $service->id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([$id]);
    }
}
