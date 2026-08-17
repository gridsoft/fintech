<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\BankTransaction;
use PDO;

class BankTransactionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** Вчитува со account_id од поврзаниот извод (за книжење без дополнителна заявка). */
    public function find(int $id): ?BankTransaction
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, s.account_id
             FROM bank_transactions t
             JOIN bank_statements s ON s.id = t.bank_statement_id
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? BankTransaction::fromRow($row) : null;
    }

    /** @return BankTransaction[] */
    public function unmatched(): array
    {
        $stmt = $this->db->query(
            "SELECT t.*, s.account_id
             FROM bank_transactions t
             JOIN bank_statements s ON s.id = t.bank_statement_id
             WHERE t.matched_status = 'unmatched'
             ORDER BY t.transaction_date DESC, t.id DESC"
        );

        return array_map([BankTransaction::class, 'fromRow'], $stmt->fetchAll());
    }

    public function create(BankTransaction $transaction): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bank_transactions (bank_statement_id, transaction_date, description, amount, direction, partner_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $transaction->bankStatementId,
            $transaction->date,
            $transaction->description,
            $transaction->amount,
            $transaction->direction,
            $transaction->partnerId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Збир на веќе матчирани трансакции за фактура — тоа Е преостанатото салдо, нема посебно чувано поле. */
    public function matchedAmountForInvoice(string $invoiceType, int $invoiceId): string
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE invoice_type = ? AND invoice_id = ? AND matched_status = 'matched'"
        );
        $stmt->execute([$invoiceType, $invoiceId]);

        return (string) $stmt->fetchColumn();
    }

    public function markMatched(int $id, string $invoiceType, int $invoiceId, int $journalEntryId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE bank_transactions SET matched_status = 'matched', invoice_type = ?, invoice_id = ?, journal_entry_id = ? WHERE id = ?"
        );
        $stmt->execute([$invoiceType, $invoiceId, $journalEntryId, $id]);
    }
}
