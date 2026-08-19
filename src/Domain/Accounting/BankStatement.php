<?php

namespace App\Domain\Accounting;

class BankStatement
{
    public ?int $id;
    public int $accountId;
    public int $currencyId;
    public string $date;
    public ?string $reference;
    public string $openingBalance;

    /** @var BankTransaction[] */
    public array $transactions = [];

    public function __construct(
        int $accountId,
        string $date,
        ?string $reference = null,
        string $openingBalance = '0.00',
        ?int $id = null,
        int $currencyId = 1
    ) {
        $this->id = $id;
        $this->accountId = $accountId;
        $this->currencyId = $currencyId;
        $this->date = $date;
        $this->reference = $reference;
        $this->openingBalance = $openingBalance;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['account_id'],
            $row['statement_date'],
            $row['reference'],
            $row['opening_balance'],
            (int) $row['id'],
            (int) $row['currency_id']
        );
    }
}
