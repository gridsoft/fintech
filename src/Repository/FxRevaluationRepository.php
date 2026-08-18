<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Accounting\FxRevaluation;
use App\Domain\Accounting\FxRevaluationLine;
use PDO;

class FxRevaluationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return FxRevaluation[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM fx_revaluations ORDER BY revaluation_date DESC, id DESC');

        return array_map([FxRevaluation::class, 'fromRow'], $stmt->fetchAll());
    }

    public function create(FxRevaluation $revaluation): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO fx_revaluations (revaluation_date, currency_id, new_rate, journal_entry_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $revaluation->date,
            $revaluation->currencyId,
            $revaluation->newRate,
            $revaluation->journalEntryId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertLine(FxRevaluationLine $line): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO fx_revaluation_lines (fx_revaluation_id, invoice_type, invoice_id, mkd_value_before, mkd_value_after, difference)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->fxRevaluationId,
            $line->invoiceType,
            $line->invoiceId,
            $line->mkdValueBefore,
            $line->mkdValueAfter,
            $line->difference,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Последната превалоризирана MKD книговодствена вредност на преостанатото
     * салдо на фактурата, ако постои претходна превалоризација — инаку null
     * (се паѓа назад на оригиналниот курс на фактурата).
     */
    public function latestValueForInvoice(string $invoiceType, int $invoiceId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT mkd_value_after FROM fx_revaluation_lines
             WHERE invoice_type = ? AND invoice_id = ?
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$invoiceType, $invoiceId]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : null;
    }

    /** @return FxRevaluationLine[] */
    public function linesForRevaluation(int $revaluationId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM fx_revaluation_lines WHERE fx_revaluation_id = ? ORDER BY id ASC');
        $stmt->execute([$revaluationId]);

        return array_map([FxRevaluationLine::class, 'fromRow'], $stmt->fetchAll());
    }
}
