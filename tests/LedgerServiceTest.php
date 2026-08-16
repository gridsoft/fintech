<?php

namespace Tests;

use App\Core\Database;
use App\Service\LedgerService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LedgerServiceTest extends TestCase
{
    private PDO $db;
    private LedgerService $ledger;
    private int $accountA;
    private int $accountB;
    private array $createdEntryIds = [];

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->ledger = new LedgerService();

        $stmt = $this->db->prepare(
            "INSERT INTO accounts (code, name, type, is_active) VALUES (?, ?, 'asset', 1)"
        );

        $stmt->execute(['TSTA' . substr(uniqid(), -8), 'Тест сметка А']);
        $this->accountA = (int) $this->db->lastInsertId();

        $stmt->execute(['TSTB' . substr(uniqid(), -8), 'Тест сметка Б']);
        $this->accountB = (int) $this->db->lastInsertId();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdEntryIds as $id) {
            $this->db->prepare('DELETE FROM journal_entries WHERE id = ?')->execute([$id]);
        }

        $this->db->prepare('DELETE FROM accounts WHERE id IN (?, ?)')->execute([$this->accountA, $this->accountB]);
    }

    public function test_it_posts_a_balanced_entry(): void
    {
        $entryId = $this->ledger->postEntry('2026-01-01', 'Балансиран тест запис', null, [
            ['account_id' => $this->accountA, 'debit' => '100.00', 'credit' => '0'],
            ['account_id' => $this->accountB, 'debit' => '0', 'credit' => '100.00'],
        ]);
        $this->createdEntryIds[] = $entryId;

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$entryId]);

        $this->assertSame(2, (int) $stmt->fetchColumn());
    }

    public function test_it_rejects_unbalanced_debit_and_credit(): void
    {
        $this->expectException(RuntimeException::class);

        $this->ledger->postEntry('2026-01-01', 'Небалансиран запис', null, [
            ['account_id' => $this->accountA, 'debit' => '100.00', 'credit' => '0'],
            ['account_id' => $this->accountB, 'debit' => '0', 'credit' => '50.00'],
        ]);
    }

    public function test_unbalanced_entry_is_not_persisted(): void
    {
        $before = (int) $this->db->query('SELECT COUNT(*) FROM journal_entries')->fetchColumn();

        try {
            $this->ledger->postEntry('2026-01-01', 'Треба да пропадне', null, [
                ['account_id' => $this->accountA, 'debit' => '100.00', 'credit' => '0'],
                ['account_id' => $this->accountB, 'debit' => '0', 'credit' => '50.00'],
            ]);
        } catch (RuntimeException $e) {
            // очекувано
        }

        $after = (int) $this->db->query('SELECT COUNT(*) FROM journal_entries')->fetchColumn();

        $this->assertSame($before, $after);
    }

    public function test_it_requires_at_least_two_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->ledger->postEntry('2026-01-01', 'Само една ставка', null, [
            ['account_id' => $this->accountA, 'debit' => '100.00', 'credit' => '0'],
        ]);
    }

    public function test_a_line_cannot_have_both_debit_and_credit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->ledger->postEntry('2026-01-01', 'Дебит и кредит на иста ставка', null, [
            ['account_id' => $this->accountA, 'debit' => '100.00', 'credit' => '50.00'],
            ['account_id' => $this->accountB, 'debit' => '0', 'credit' => '50.00'],
        ]);
    }

    public function test_it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->ledger->postEntry('2026-01-01', 'Негативен износ', null, [
            ['account_id' => $this->accountA, 'debit' => '-10', 'credit' => '0'],
            ['account_id' => $this->accountB, 'debit' => '0', 'credit' => '10'],
        ]);
    }
}
