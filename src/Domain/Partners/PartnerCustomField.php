<?php

namespace App\Domain\Partners;

/**
 * Резервен канал само за навистина непредвидливи полиња кои не влегуваат
 * во стандардниот сет колони на Partner — не се користи за реплицирање
 * на веќе познати полиња (адреса, контакт, банкарски детали). Види
 * DECISIONS.md за образложението зошто не е избран целосен EAV дизајн.
 */
class PartnerCustomField
{
    public ?int $id;
    public int $partnerId;
    public string $key;
    public ?string $value;

    public function __construct(int $partnerId, string $key, ?string $value = null, ?int $id = null)
    {
        $this->id = $id;
        $this->partnerId = $partnerId;
        $this->key = $key;
        $this->value = $value;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['partner_id'],
            $row['field_key'],
            $row['field_value'],
            (int) $row['id']
        );
    }
}
