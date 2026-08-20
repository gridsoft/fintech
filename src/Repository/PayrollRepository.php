<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Payroll\PayrollRun;
use App\Domain\Payroll\PayrollSettings;
use App\Domain\Payroll\Payslip;
use PDO;
use RuntimeException;

class PayrollRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** Секогаш точно еден ред (внесен во migration 026), исто како базната валута во CurrencyRepository::base(). */
    public function getSettings(): PayrollSettings
    {
        $stmt = $this->db->query('SELECT * FROM payroll_settings ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Не постојат поставки за плата во системот.');
        }

        return PayrollSettings::fromRow($row);
    }

    public function updateSettings(PayrollSettings $settings): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payroll_settings SET pension_rate = ?, health_rate = ?, employment_rate = ?, pit_rate = ?, seniority_rate_per_year = ?, sick_leave_pay_rate = ?, shift_day_rate = ?, holiday_day_rate = ?, daily_rate_divisor = ?, personal_exemption = ? WHERE id = ?'
        );
        $stmt->execute([
            $settings->pensionRate,
            $settings->healthRate,
            $settings->employmentRate,
            $settings->pitRate,
            $settings->seniorityRatePerYear,
            $settings->sickLeavePayRate,
            $settings->shiftDayRate,
            $settings->holidayDayRate,
            $settings->dailyRateDivisor,
            $settings->personalExemption,
            $settings->id,
        ]);
    }

    public function hasRunForPeriod(string $periodDate): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM payroll_runs WHERE period_date = ?');
        $stmt->execute([$periodDate]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createRun(PayrollRun $run): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payroll_runs (period_date, journal_entry_id, total_gross, total_seniority_supplement, total_sick_deduction, total_shift_supplement, total_holiday_supplement, total_net, total_pit, total_pension, total_health, total_employment)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $run->periodDate,
            $run->journalEntryId,
            $run->totalGross,
            $run->totalSenioritySupplement,
            $run->totalSickDeduction,
            $run->totalShiftSupplement,
            $run->totalHolidaySupplement,
            $run->totalNet,
            $run->totalPit,
            $run->totalPension,
            $run->totalHealth,
            $run->totalEmployment,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertPayslip(Payslip $payslip): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payslips (payroll_run_id, employee_id, base_salary, seniority_months, seniority_supplement, daily_rate, sick_days, sick_deduction, shift_days, shift_supplement, holiday_days, holiday_supplement, gross_salary, pension_contribution, health_contribution, employment_contribution, taxable_base, pit, net_salary)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $payslip->payrollRunId,
            $payslip->employeeId,
            $payslip->baseSalary,
            $payslip->seniorityMonths,
            $payslip->senioritySupplement,
            $payslip->dailyRate,
            $payslip->sickDays,
            $payslip->sickDeduction,
            $payslip->shiftDays,
            $payslip->shiftSupplement,
            $payslip->holidayDays,
            $payslip->holidaySupplement,
            $payslip->grossSalary,
            $payslip->pensionContribution,
            $payslip->healthContribution,
            $payslip->employmentContribution,
            $payslip->taxableBase,
            $payslip->pit,
            $payslip->netSalary,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return PayrollRun[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM payroll_runs ORDER BY period_date DESC, id DESC');

        return array_map([PayrollRun::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?PayrollRun
    {
        $stmt = $this->db->prepare('SELECT * FROM payroll_runs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? PayrollRun::fromRow($row) : null;
    }

    /** @return Payslip[] */
    public function payslipsForRun(int $payrollRunId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM payslips WHERE payroll_run_id = ? ORDER BY id ASC');
        $stmt->execute([$payrollRunId]);

        return array_map([Payslip::class, 'fromRow'], $stmt->fetchAll());
    }
}
