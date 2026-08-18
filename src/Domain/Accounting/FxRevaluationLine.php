<?php

namespace App\Domain\Accounting;

class FxRevaluationLine
{
    public const INVOICE_TYPES = ['sales', 'purchase'];

    public ?int $id;
    public int $fxRevaluationId;
    public string $invoiceType;
    public int $invoiceId;
    public string $mkdValueBefore;
    public string $mkdValueAfter;
    public string $difference;

    public function __construct(
        int $fxRevaluationId,
        string $invoiceType,
        int $invoiceId,
        string $mkdValueBefore,
        string $mkdValueAfter,
        string $difference,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->fxRevaluationId = $fxRevaluationId;
        $this->invoiceType = $invoiceType;
        $this->invoiceId = $invoiceId;
        $this->mkdValueBefore = $mkdValueBefore;
        $this->mkdValueAfter = $mkdValueAfter;
        $this->difference = $difference;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['fx_revaluation_id'],
            $row['invoice_type'],
            (int) $row['invoice_id'],
            $row['mkd_value_before'],
            $row['mkd_value_after'],
            $row['difference'],
            (int) $row['id']
        );
    }
}
