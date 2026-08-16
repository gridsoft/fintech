<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Partners\Partner;
use PDO;

class PartnerRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Partner[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM partners ORDER BY name ASC');

        return array_map([Partner::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Partner
    {
        $stmt = $this->db->prepare('SELECT * FROM partners WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Partner::fromRow($row) : null;
    }

    public function create(Partner $partner): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO partners (name, type, tax_number, address, contact, country, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $partner->name,
            $partner->type,
            $partner->taxNumber,
            $partner->address,
            $partner->contact,
            $partner->country,
            $partner->isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Partner $partner): void
    {
        $stmt = $this->db->prepare(
            'UPDATE partners SET name = ?, type = ?, tax_number = ?, address = ?, contact = ?, country = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $partner->name,
            $partner->type,
            $partner->taxNumber,
            $partner->address,
            $partner->contact,
            $partner->country,
            $partner->isActive ? 1 : 0,
            $partner->id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM partners WHERE id = ?');
        $stmt->execute([$id]);
    }
}
