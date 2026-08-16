<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\JournalEntry;
use App\Domain\Accounting\JournalLine;
use PDO;

class JournalRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function insertEntry(JournalEntry $entry): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO journal_entries (entry_date, description, reference) VALUES (?, ?, ?)'
        );
        $stmt->execute([$entry->date, $entry->description, $entry->reference]);

        return (int) $this->db->lastInsertId();
    }

    public function insertLine(JournalLine $line): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, description) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->journalEntryId,
            $line->accountId,
            $line->debit,
            $line->credit,
            $line->description,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return JournalEntry[] листа на записи со вкупен дебит по запис, најнови први */
    public function allWithTotals(): array
    {
        $stmt = $this->db->query(
            'SELECT e.*, COALESCE(SUM(l.debit), 0) AS total
             FROM journal_entries e
             LEFT JOIN journal_lines l ON l.journal_entry_id = e.id
             GROUP BY e.id
             ORDER BY e.entry_date DESC, e.id DESC'
        );

        $entries = [];
        foreach ($stmt->fetchAll() as $row) {
            $entry = JournalEntry::fromRow($row);
            $entry->total = $row['total'];
            $entries[] = $entry;
        }

        return $entries;
    }

    public function find(int $id): ?JournalEntry
    {
        $stmt = $this->db->prepare('SELECT * FROM journal_entries WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $entry = JournalEntry::fromRow($row);
        $entry->lines = $this->linesForEntry($id);

        return $entry;
    }

    /** @return JournalLine[] */
    public function linesForEntry(int $entryId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM journal_lines WHERE journal_entry_id = ? ORDER BY id ASC');
        $stmt->execute([$entryId]);

        return array_map([JournalLine::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Сите линии за една сметка, со податоци од записот, хронолошки подредени.
     * @return array<int, array{line: JournalLine, entry: JournalEntry}>
     */
    public function linesForAccount(int $accountId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, e.entry_date, e.description AS entry_description, e.reference
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.journal_entry_id
             WHERE l.account_id = ?
             ORDER BY e.entry_date ASC, e.id ASC, l.id ASC'
        );
        $stmt->execute([$accountId]);

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $line = JournalLine::fromRow($row);
            $entry = new JournalEntry($row['entry_date'], $row['entry_description'], $row['reference'], (int) $row['journal_entry_id']);
            $results[] = ['line' => $line, 'entry' => $entry];
        }

        return $results;
    }
}
