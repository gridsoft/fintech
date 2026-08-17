<?php

namespace App\Domain\Partners;

class PartnerEmployee
{
    public ?int $id;
    public int $partnerId;
    public string $name;
    public ?string $jobTitle;
    public ?string $phone;
    public ?string $email;

    public function __construct(
        int $partnerId,
        string $name,
        ?string $jobTitle = null,
        ?string $phone = null,
        ?string $email = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->partnerId = $partnerId;
        $this->name = $name;
        $this->jobTitle = $jobTitle;
        $this->phone = $phone;
        $this->email = $email;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['partner_id'],
            $row['name'],
            $row['job_title'],
            $row['phone'],
            $row['email'],
            (int) $row['id']
        );
    }
}
