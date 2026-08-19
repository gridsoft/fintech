<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\BankStatement;
use App\Domain\Accounting\BankTransaction;
use PDO;

class BankStatementRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return BankStatement[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM bank_statements ORDER BY statement_date DESC, id DESC');

        return array_map([BankStatement::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?BankStatement
    {
        $stmt = $this->db->prepare('SELECT * FROM bank_statements WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $statement = BankStatement::fromRow($row);
        $statement->transactions = $this->transactionsForStatement($id);

        return $statement;
    }

    /** @return BankTransaction[] */
    public function transactionsForStatement(int $statementId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM bank_transactions WHERE bank_statement_id = ? ORDER BY id ASC');
        $stmt->execute([$statementId]);

        return array_map([BankTransaction::class, 'fromRow'], $stmt->fetchAll());
    }

    public function create(BankStatement $statement): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bank_statements (account_id, currency_id, statement_date, reference, opening_balance) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$statement->accountId, $statement->currencyId, $statement->date, $statement->reference, $statement->openingBalance]);

        return (int) $this->db->lastInsertId();
    }
}
