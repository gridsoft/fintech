<?php

namespace App\Service;

use App\Core\Database;
use App\Domain\Accounting\JournalEntry;
use App\Domain\Accounting\JournalLine;
use App\Repository\JournalRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class LedgerService
{
    private PDO $db;
    private JournalRepository $journal;

    public function __construct(?JournalRepository $journal = null)
    {
        $this->db = Database::connection();
        $this->journal = $journal ?? new JournalRepository();
    }

    /**
     * Го книжи записот во главната книга.
     *
     * @param array<int, array{account_id: int, debit?: string|float, credit?: string|float, description?: ?string}> $lines
     * @throws InvalidArgumentException при невалидни ставки
     * @throws RuntimeException кога дебит и кредит не се балансирани
     */
    public function postEntry(string $date, string $description, ?string $reference, array $lines): int
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('Записот мора да содржи барем 2 ставки.');
        }

        $totalDebit = '0.00';
        $totalCredit = '0.00';
        $normalized = [];

        foreach ($lines as $line) {
            if (empty($line['account_id'])) {
                throw new InvalidArgumentException('Секоја ставка мора да има избрано сметка.');
            }

            $debit = $this->toDecimal($line['debit'] ?? 0);
            $credit = $this->toDecimal($line['credit'] ?? 0);

            if (bccomp($debit, '0.00', 2) < 0 || bccomp($credit, '0.00', 2) < 0) {
                throw new InvalidArgumentException('Износите не можат да бидат негативни.');
            }

            if (bccomp($debit, '0.00', 2) > 0 && bccomp($credit, '0.00', 2) > 0) {
                throw new InvalidArgumentException('Ставка не може истовремено да има дебит и кредит.');
            }

            if (bccomp($debit, '0.00', 2) === 0 && bccomp($credit, '0.00', 2) === 0) {
                throw new InvalidArgumentException('Секоја ставка мора да има дебит или кредит поголем од нула.');
            }

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);

            $normalized[] = [
                'account_id' => (int) $line['account_id'],
                'partner_id' => !empty($line['partner_id']) ? (int) $line['partner_id'] : null,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
            ];
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw new RuntimeException("Дебит ($totalDebit) и кредит ($totalCredit) не се балансирани.");
        }

        $this->db->beginTransaction();

        try {
            $entryId = $this->journal->insertEntry(new JournalEntry($date, $description, $reference));

            foreach ($normalized as $line) {
                $this->journal->insertLine(new JournalLine(
                    $line['account_id'],
                    $line['debit'],
                    $line['credit'],
                    $line['description'],
                    $entryId,
                    $line['partner_id']
                ));
            }

            $this->db->commit();

            return $entryId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Сторнира веќе постоечки запис — нов, целосно ист запис со заменети
     * дебит/кредит на секоја линија (не бришење на оригиналот, за да остане
     * ревизорска трага). Автоматски балансиран бидејќи е огледало на веќе
     * балансиран запис.
     */
    public function reverseEntry(int $entryId, string $date, string $description, ?string $reference): int
    {
        $lines = $this->journal->linesForEntry($entryId);

        if (!$lines) {
            throw new InvalidArgumentException("Записот #$entryId нема ставки за сторнирање.");
        }

        $reversed = array_map(static fn ($line) => [
            'account_id' => $line->accountId,
            'partner_id' => $line->partnerId,
            'debit' => $line->credit,
            'credit' => $line->debit,
            'description' => $description,
        ], $lines);

        return $this->postEntry($date, $description, $reference, $reversed);
    }

    private function toDecimal($value): string
    {
        if ($value === '' || $value === null) {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
