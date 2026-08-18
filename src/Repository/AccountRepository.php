<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\Account;
use PDO;

class AccountRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Account[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM accounts ORDER BY code ASC');

        return array_map([Account::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Account
    {
        $stmt = $this->db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Account::fromRow($row) : null;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
    }

    /** Само "постирачки" (синтетички) сметки — на нив реално се книжи, за разлика од класа/група хедерите. */
    public function postable(): array
    {
        $stmt = $this->db->query("SELECT * FROM accounts WHERE CHAR_LENGTH(code) >= 3 ORDER BY code ASC");

        return array_map([Account::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Постирачки сметки од само еден тип (на пр. 'revenue' за приходни сметки во категории). */
    public function postableByType(string $type): array
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE CHAR_LENGTH(code) >= 3 AND type = ? ORDER BY code ASC");
        $stmt->execute([$type]);

        return array_map([Account::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Сметки од група 10 (Парични средства) — валидни за банкарски извод.
     * Вклучува и аналитички под-сметки (пр. "1000" за конкретна банкарска
     * сметка), не само тро-цифрените синтетички сметки — фирма може да има
     * повеќе банки/сметки (денарски и девизни) под истата синтетичка сметка.
     */
    public function cashAccounts(): array
    {
        $stmt = $this->db->query("SELECT * FROM accounts WHERE code LIKE '10%' AND CHAR_LENGTH(code) >= 3 ORDER BY code ASC");

        return array_map([Account::class, 'fromRow'], $stmt->fetchAll());
    }

    public function findByCode(string $code): ?Account
    {
        $stmt = $this->db->prepare('SELECT * FROM accounts WHERE code = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch();

        return $row ? Account::fromRow($row) : null;
    }

    /** @return array<int, string> id => "code — name", за parent избирач */
    public function options(?int $excludeId = null): array
    {
        $sql = 'SELECT id, code, name FROM accounts';
        $params = [];

        if ($excludeId !== null) {
            $sql .= ' WHERE id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' ORDER BY code ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $options = [];
        foreach ($stmt->fetchAll() as $row) {
            $options[(int) $row['id']] = "{$row['code']} — {$row['name']}";
        }

        return $options;
    }

    public function create(Account $account): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO accounts (code, name, type, parent_id, is_active) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $account->code,
            $account->name,
            $account->type,
            $account->parentId,
            $account->isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Account $account): void
    {
        $stmt = $this->db->prepare(
            'UPDATE accounts SET code = ?, name = ?, type = ?, parent_id = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $account->code,
            $account->name,
            $account->type,
            $account->parentId,
            $account->isActive ? 1 : 0,
            $account->id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM accounts WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM accounts WHERE code = ?';
        $params = [$code];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }
}
