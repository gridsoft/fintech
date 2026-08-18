<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\AdvanceApplication;
use App\Domain\Accounting\BankTransaction;
use PDO;

class AdvanceApplicationRepository
{
    public const RECEIVED_ADVANCE_CODES = ['2230', '2231'];
    public const GIVEN_ADVANCE_CODES = ['3700', '3701'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** Веќе применето на фактура — SUM, нема посебно чувано поле (истиот образец како matchedAmountForInvoice()). */
    public function appliedAmountForInvoice(string $invoiceType, int $invoiceId): string
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM advance_applications WHERE invoice_type = ? AND invoice_id = ?'
        );
        $stmt->execute([$invoiceType, $invoiceId]);

        return (string) $stmt->fetchColumn();
    }

    /** Веќе искористено од авансот (на било кои фактури) — за да се пресмета преостанатото на самиот аванс. */
    public function appliedAmountForTransaction(int $bankTransactionId): string
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM advance_applications WHERE bank_transaction_id = ?'
        );
        $stmt->execute([$bankTransactionId]);

        return (string) $stmt->fetchColumn();
    }

    public function create(AdvanceApplication $application): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO advance_applications (bank_transaction_id, invoice_type, invoice_id, amount, journal_entry_id, applied_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $application->bankTransactionId,
            $application->invoiceType,
            $application->invoiceId,
            $application->amount,
            $application->journalEntryId,
            $application->appliedDate,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return array<int, array{transaction: BankTransaction, remaining: string}> */
    public function openReceivedAdvances(): array
    {
        return $this->openAdvances(self::RECEIVED_ADVANCE_CODES);
    }

    /** @return array<int, array{transaction: BankTransaction, remaining: string}> */
    public function openGivenAdvances(): array
    {
        return $this->openAdvances(self::GIVEN_ADVANCE_CODES);
    }

    /** @param string[] $accountCodes */
    private function openAdvances(array $accountCodes): array
    {
        $placeholders = implode(',', array_fill(0, count($accountCodes), '?'));

        $stmt = $this->db->prepare(
            "SELECT t.*, s.account_id
             FROM bank_transactions t
             JOIN bank_statements s ON s.id = t.bank_statement_id
             JOIN accounts a ON a.id = t.gl_account_id
             WHERE a.code IN ($placeholders) AND t.matched_status = 'matched'
             ORDER BY t.transaction_date ASC, t.id ASC"
        );
        $stmt->execute($accountCodes);

        $result = [];

        foreach ($stmt->fetchAll() as $row) {
            $transaction = BankTransaction::fromRow($row);
            $remaining = bcsub($transaction->amount, $this->appliedAmountForTransaction($transaction->id), 2);

            if (bccomp($remaining, '0.00', 2) > 0) {
                $result[] = ['transaction' => $transaction, 'remaining' => $remaining];
            }
        }

        return $result;
    }
}
