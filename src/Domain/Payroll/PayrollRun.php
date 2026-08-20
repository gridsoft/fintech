<?php

namespace App\Domain\Payroll;

class PayrollRun
{
    public ?int $id;
    public string $periodDate;
    public int $journalEntryId;
    public string $totalGross;
    public string $totalSenioritySupplement;
    public string $totalSickDeduction;
    public string $totalShiftSupplement;
    public string $totalHolidaySupplement;
    public string $totalNet;
    public string $totalPit;
    public string $totalPension;
    public string $totalHealth;
    public string $totalEmployment;

    public function __construct(
        string $periodDate,
        int $journalEntryId,
        string $totalGross,
        string $totalSenioritySupplement,
        string $totalSickDeduction,
        string $totalShiftSupplement,
        string $totalHolidaySupplement,
        string $totalNet,
        string $totalPit,
        string $totalPension,
        string $totalHealth,
        string $totalEmployment,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->periodDate = $periodDate;
        $this->journalEntryId = $journalEntryId;
        $this->totalGross = $totalGross;
        $this->totalSenioritySupplement = $totalSenioritySupplement;
        $this->totalSickDeduction = $totalSickDeduction;
        $this->totalShiftSupplement = $totalShiftSupplement;
        $this->totalHolidaySupplement = $totalHolidaySupplement;
        $this->totalNet = $totalNet;
        $this->totalPit = $totalPit;
        $this->totalPension = $totalPension;
        $this->totalHealth = $totalHealth;
        $this->totalEmployment = $totalEmployment;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['period_date'],
            (int) $row['journal_entry_id'],
            $row['total_gross'],
            $row['total_seniority_supplement'],
            $row['total_sick_deduction'],
            $row['total_shift_supplement'],
            $row['total_holiday_supplement'],
            $row['total_net'],
            $row['total_pit'],
            $row['total_pension'],
            $row['total_health'],
            $row['total_employment'],
            (int) $row['id']
        );
    }
}
