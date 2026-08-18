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
            'INSERT INTO bank_transactions (bank_statement_id, transaction_date, description, code, amount, balance_after, direction, partner_id, gl_account_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $transaction->bankStatementId,
            $transaction->date,
            $transaction->description,
            $transaction->code,
            $transaction->amount,
            $transaction->balanceAfter,
            $transaction->direction,
            $transaction->partnerId,
            $transaction->glAccountId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Салдо на последната трансакција во изводот, или почетното салдо ако сеуште нема трансакции. */
    public function lastBalance(int $statementId): string
    {
        $stmt = $this->db->prepare(
            'SELECT balance_after FROM bank_transactions WHERE bank_statement_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$statementId]);
        $balance = $stmt->fetchColumn();

        if ($balance !== false && $balance !== null) {
            return (string) $balance;
        }

        $stmt = $this->db->prepare('SELECT opening_balance FROM bank_statements WHERE id = ?');
        $stmt->execute([$statementId]);

        return (string) $stmt->fetchColumn();
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

    public function markMatched(int $id, ?string $invoiceType, ?int $invoiceId, int $journalEntryId, int $glAccountId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE bank_transactions SET matched_status = 'matched', invoice_type = ?, invoice_id = ?, journal_entry_id = ?, gl_account_id = ? WHERE id = ?"
        );
        $stmt->execute([$invoiceType, $invoiceId, $journalEntryId, $glAccountId, $id]);
    }
}
