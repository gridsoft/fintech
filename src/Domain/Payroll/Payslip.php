<?php

namespace App\Domain\Payroll;

class Payslip
{
    public ?int $id;
    public int $payrollRunId;
    public int $employeeId;
    public string $baseSalary;
    public int $seniorityMonths;
    public string $senioritySupplement;
    public string $dailyRate;
    public int $sickDays;
    public string $sickDeduction;
    public int $shiftDays;
    public string $shiftSupplement;
    public int $holidayDays;
    public string $holidaySupplement;
    public string $grossSalary;
    public string $pensionContribution;
    public string $healthContribution;
    public string $employmentContribution;
    public string $taxableBase;
    public string $pit;
    public string $netSalary;

    public function __construct(
        int $payrollRunId,
        int $employeeId,
        string $baseSalary,
        int $seniorityMonths,
        string $senioritySupplement,
        string $dailyRate,
        int $sickDays,
        string $sickDeduction,
        int $shiftDays,
        string $shiftSupplement,
        int $holidayDays,
        string $holidaySupplement,
        string $grossSalary,
        string $pensionContribution,
        string $healthContribution,
        string $employmentContribution,
        string $taxableBase,
        string $pit,
        string $netSalary,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->payrollRunId = $payrollRunId;
        $this->employeeId = $employeeId;
        $this->baseSalary = $baseSalary;
        $this->seniorityMonths = $seniorityMonths;
        $this->senioritySupplement = $senioritySupplement;
        $this->dailyRate = $dailyRate;
        $this->sickDays = $sickDays;
        $this->sickDeduction = $sickDeduction;
        $this->shiftDays = $shiftDays;
        $this->shiftSupplement = $shiftSupplement;
        $this->holidayDays = $holidayDays;
        $this->holidaySupplement = $holidaySupplement;
        $this->grossSalary = $grossSalary;
        $this->pensionContribution = $pensionContribution;
        $this->healthContribution = $healthContribution;
        $this->employmentContribution = $employmentContribution;
        $this->taxableBase = $taxableBase;
        $this->pit = $pit;
        $this->netSalary = $netSalary;
    }

    /** Стаж во завршени години (не заокружено) — за прикажување на платната листа. */
    public function seniorityYears(): int
    {
        return intdiv($this->seniorityMonths, 12);
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['payroll_run_id'],
            (int) $row['employee_id'],
            $row['base_salary'],
            (int) $row['seniority_months'],
            $row['seniority_supplement'],
            $row['daily_rate'],
            (int) $row['sick_days'],
            $row['sick_deduction'],
            (int) $row['shift_days'],
            $row['shift_supplement'],
            (int) $row['holiday_days'],
            $row['holiday_supplement'],
            $row['gross_salary'],
            $row['pension_contribution'],
            $row['health_contribution'],
            $row['employment_contribution'],
            $row['taxable_base'],
            $row['pit'],
            $row['net_salary'],
            (int) $row['id']
        );
    }
}
