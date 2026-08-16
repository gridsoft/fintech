<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\Terk;
use App\Domain\Accounting\TerkLine;
use PDO;

class TerkRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Terk[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM terkovi ORDER BY name ASC');

        return array_map([Terk::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return array<int, int> terk_id => број на ставки */
    public function lineCounts(): array
    {
        $stmt = $this->db->query('SELECT terk_id, COUNT(*) AS cnt FROM terk_lines GROUP BY terk_id');

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['terk_id']] = (int) $row['cnt'];
        }

        return $counts;
    }

    public function find(int $id): ?Terk
    {
        $stmt = $this->db->prepare('SELECT * FROM terkovi WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $terk = Terk::fromRow($row);
        $terk->lines = $this->linesForTerk($id);

        return $terk;
    }

    /** @return TerkLine[] */
    public function linesForTerk(int $terkId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM terk_lines WHERE terk_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$terkId]);

        return array_map([TerkLine::class, 'fromRow'], $stmt->fetchAll());
    }

    public function create(Terk $terk): int
    {
        $stmt = $this->db->prepare('INSERT INTO terkovi (name, description, is_active) VALUES (?, ?, ?)');
        $stmt->execute([$terk->name, $terk->description, $terk->isActive ? 1 : 0]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Terk $terk): void
    {
        $stmt = $this->db->prepare('UPDATE terkovi SET name = ?, description = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$terk->name, $terk->description, $terk->isActive ? 1 : 0, $terk->id]);
    }

    public function deleteLines(int $terkId): void
    {
        $stmt = $this->db->prepare('DELETE FROM terk_lines WHERE terk_id = ?');
        $stmt->execute([$terkId]);
    }

    public function insertLine(TerkLine $line): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO terk_lines (terk_id, account_id, side, amount_source, tag_partner, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->terkId,
            $line->accountId,
            $line->side,
            $line->amountSource,
            $line->tagPartner ? 1 : 0,
            $line->sortOrder,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM terkovi WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function isUsedByNalog(int $terkId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM nalozi WHERE terk_id = ?');
        $stmt->execute([$terkId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
