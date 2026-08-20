<?php

namespace App\Domain\Payroll;

class PayrollSettings
{
    public ?int $id;
    public string $pensionRate;
    public string $healthRate;
    public string $employmentRate;
    public string $pitRate;
    public string $seniorityRatePerYear;
    public string $sickLeavePayRate;
    public string $shiftDayRate;
    public string $holidayDayRate;
    public int $dailyRateDivisor;
    public string $personalExemption;

    public function __construct(
        string $pensionRate,
        string $healthRate,
        string $employmentRate,
        string $pitRate,
        string $seniorityRatePerYear,
        string $sickLeavePayRate,
        string $shiftDayRate,
        string $holidayDayRate,
        int $dailyRateDivisor,
        string $personalExemption,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->pensionRate = $pensionRate;
        $this->healthRate = $healthRate;
        $this->employmentRate = $employmentRate;
        $this->pitRate = $pitRate;
        $this->seniorityRatePerYear = $seniorityRatePerYear;
        $this->sickLeavePayRate = $sickLeavePayRate;
        $this->shiftDayRate = $shiftDayRate;
        $this->holidayDayRate = $holidayDayRate;
        $this->dailyRateDivisor = $dailyRateDivisor;
        $this->personalExemption = $personalExemption;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['pension_rate'],
            $row['health_rate'],
            $row['employment_rate'],
            $row['pit_rate'],
            $row['seniority_rate_per_year'],
            $row['sick_leave_pay_rate'],
            $row['shift_day_rate'],
            $row['holiday_day_rate'],
            (int) $row['daily_rate_divisor'],
            $row['personal_exemption'],
            (int) $row['id']
        );
    }
}
