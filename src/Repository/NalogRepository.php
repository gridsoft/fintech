<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\Nalog;
use PDO;

class NalogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Nalog[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM nalozi ORDER BY name ASC');

        return array_map([Nalog::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return Nalog[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM nalozi WHERE is_active = 1 ORDER BY name ASC');

        return array_map([Nalog::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Nalog
    {
        $stmt = $this->db->prepare('SELECT * FROM nalozi WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Nalog::fromRow($row) : null;
    }

    public function create(Nalog $nalog): int
    {
        $stmt = $this->db->prepare('INSERT INTO nalozi (name, terk_id, is_active) VALUES (?, ?, ?)');
        $stmt->execute([$nalog->name, $nalog->terkId, $nalog->isActive ? 1 : 0]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Nalog $nalog): void
    {
        $stmt = $this->db->prepare('UPDATE nalozi SET name = ?, terk_id = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$nalog->name, $nalog->terkId, $nalog->isActive ? 1 : 0, $nalog->id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM nalozi WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function isUsedByInvoice(int $nalogId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM invoices WHERE nalog_id = ?');
        $stmt->execute([$nalogId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
