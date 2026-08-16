<?php

namespace App\Domain\Accounting;

class TerkLine
{
    public const SIDES = ['debit', 'credit'];
    public const SOURCES = ['net', 'vat', 'gross'];

    public const SOURCE_LABELS = [
        'net' => 'Нето',
        'vat' => 'ДДВ',
        'gross' => 'Бруто',
    ];

    public ?int $id;
    public ?int $terkId;
    public int $accountId;
    public string $side;
    public string $amountSource;
    public bool $tagPartner;
    public int $sortOrder;

    public function __construct(
        int $accountId,
        string $side,
        string $amountSource,
        bool $tagPartner = false,
        int $sortOrder = 0,
        ?int $terkId = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->terkId = $terkId;
        $this->accountId = $accountId;
        $this->side = $side;
        $this->amountSource = $amountSource;
        $this->tagPartner = $tagPartner;
        $this->sortOrder = $sortOrder;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['account_id'],
            $row['side'],
            $row['amount_source'],
            (bool) $row['tag_partner'],
            (int) $row['sort_order'],
            (int) $row['terk_id'],
            (int) $row['id']
        );
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->amountSource] ?? $this->amountSource;
    }
}
