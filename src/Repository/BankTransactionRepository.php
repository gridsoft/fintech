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
            'INSERT INTO bank_transactions (bank_statement_id, transaction_date, description, code, amount, exchange_rate, balance_after, direction, partner_id, gl_account_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $transaction->bankStatementId,
            $transaction->date,
            $transaction->description,
            $transaction->code,
            $transaction->amount,
            $transaction->exchangeRate,
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

    /**
     * Збир на веќе матчирани трансакции за фактура, во MKD — тоа Е
     * преостанатото салдо, нема посебно чувано поле. `amount * exchange_rate`
     * го дава MKD-еквивалентот; за денарски трансакции exchange_rate е
     * секогаш 1.000000, без ефект на резултатот (исто како
     * BankTransaction::amountInBaseCurrency(), само на SQL ниво за збирот).
     * CAST(... AS DECIMAL(15,2)) е задолжителен — MySQL го проширува
     * decimal-скалата на производ/сума (amount 2 + exchange_rate 6 децимали)
     * и без CAST враќа "700.00000000" наместо "700.00".
     */
    public function matchedAmountForInvoice(string $invoiceType, int $invoiceId): string
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(CAST(SUM(amount * exchange_rate) AS DECIMAL(15,2)), 0) FROM bank_transactions WHERE invoice_type = ? AND invoice_id = ? AND matched_status = 'matched'"
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
