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
            'INSERT INTO journal_lines (journal_entry_id, account_id, partner_id, debit, credit, description) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->journalEntryId,
            $line->accountId,
            $line->partnerId,
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

    /**
     * Сите линии за еден партнер, со податоци од записот и сметката, хронолошки подредени.
     * @return array<int, array{line: JournalLine, entry: JournalEntry, account_code: string, account_name: string}>
     */
    public function linesForPartner(int $partnerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, e.entry_date, e.description AS entry_description, e.reference,
                    a.code AS account_code, a.name AS account_name
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.journal_entry_id
             JOIN accounts a ON a.id = l.account_id
             WHERE l.partner_id = ?
             ORDER BY e.entry_date ASC, e.id ASC, l.id ASC'
        );
        $stmt->execute([$partnerId]);

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $line = JournalLine::fromRow($row);
            $entry = new JournalEntry($row['entry_date'], $row['entry_description'], $row['reference'], (int) $row['journal_entry_id']);
            $results[] = [
                'line' => $line,
                'entry' => $entry,
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
            ];
        }

        return $results;
    }

    /** @return array<int, array{account_id: int, debit: string, credit: string}> само сметки со активност */
    public function balancesByAccount(): array
    {
        $stmt = $this->db->query(
            'SELECT account_id, SUM(debit) AS debit, SUM(credit) AS credit
             FROM journal_lines
             GROUP BY account_id
             HAVING SUM(debit) <> 0 OR SUM(credit) <> 0'
        );

        return $stmt->fetchAll();
    }

    /** @return array{debit: string, credit: string} */
    public function sumForAccountCodeInPeriod(string $code, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(l.debit), 0) AS debit, COALESCE(SUM(l.credit), 0) AS credit
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.journal_entry_id
             JOIN accounts a ON a.id = l.account_id
             WHERE a.code = ? AND e.entry_date BETWEEN ? AND ?'
        );
        $stmt->execute([$code, $from, $to]);

        return $stmt->fetch();
    }

    /** @return array<int, array{partner_id: int, debit: string, credit: string}> */
    public function balancesByPartner(): array
    {
        $stmt = $this->db->query(
            'SELECT partner_id, SUM(debit) AS debit, SUM(credit) AS credit
             FROM journal_lines
             WHERE partner_id IS NOT NULL
             GROUP BY partner_id'
        );

        return $stmt->fetchAll();
    }
}
