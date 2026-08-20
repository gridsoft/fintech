<?php

namespace App\Service;

use App\Domain\Payroll\PayrollRun;
use App\Domain\Payroll\Payslip;
use App\Repository\AccountRepository;
use App\Repository\EmployeeRepository;
use App\Repository\PayrollRepository;
use DateTime;
use InvalidArgumentException;

/**
 * Плата (Фаза 11). Сметките за книжење се хардкодирани — веќе постојат во
 * клиентскиот контен план (migration 018), исто како депрецијацијата
 * (FixedAssetService) нема легитимна причина корисникот да бира различни
 * сметки по извршување.
 */
class PayrollService
{
    private const ACCOUNT_GROSS_EXPENSE = '42100';
    private const ACCOUNT_NET_PAYABLE = '2400';
    private const ACCOUNT_PIT_PAYABLE = '23400';
    private const ACCOUNT_PENSION_PAYABLE = '2342';
    private const ACCOUNT_HEALTH_PAYABLE = '2344';
    private const ACCOUNT_EMPLOYMENT_PAYABLE = '2346';

    private EmployeeRepository $employees;
    private PayrollRepository $payroll;
    private AccountRepository $accounts;
    private LedgerService $ledger;

    public function __construct(
        ?EmployeeRepository $employees = null,
        ?PayrollRepository $payroll = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null
    ) {
        $this->employees = $employees ?? new EmployeeRepository();
        $this->payroll = $payroll ?? new PayrollRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    /**
     * Активни вработени што важат за периодот (истата исклучувачка логика
     * како во runPayroll()) — искористено и од подготвителниот чекор
     * (/payroll/prepare) за да се прикаже точно кој ќе биде опфатен.
     *
     * @return \App\Domain\Payroll\Employee[]
     */
    public function eligibleEmployeesForPeriod(string $periodDate): array
    {
        $eligible = [];

        foreach ($this->employees->allActive() as $employee) {
            if ($employee->hireDate > $periodDate) {
                continue; // сè уште не е вработен(а) во овој период
            }

            if ($employee->terminationDate !== null && $employee->terminationDate < $periodDate) {
                continue; // веќе не е вработен(а) во овој период
            }

            $eligible[] = $employee;
        }

        return $eligible;
    }

    /**
     * Пресметува плата за сите активни вработени за периодот и книжи ЕДЕН
     * заеднички journal entry (не еден по вработен), исто како
     * FixedAssetService::runDepreciation(). Идемпотентно — повторно
     * извршување за истиот период е "no-op" (payroll_runs UNIQUE(period_date)).
     *
     * $variableInputsByEmployeeId ги носи месечните варијабли внесени на
     * подготвителниот чекор (боледување/смени/празници) — не се чуваат на
     * вработениот, туку се внесуваат одново за секој период. Недостасува ли
     * влез за некој вработен, се смета 0 за сите три.
     *
     * @param array<int, array{sick_days?: int, shift_days?: int, holiday_days?: int}> $variableInputsByEmployeeId
     * @return array{count: int, total_gross: string, total_net: string}
     */
    public function runPayroll(string $periodDate, array $variableInputsByEmployeeId = []): array
    {
        if ($this->payroll->hasRunForPeriod($periodDate)) {
            return ['count' => 0, 'total_gross' => '0.00', 'total_net' => '0.00'];
        }

        $grossAccount = $this->accounts->findByCode(self::ACCOUNT_GROSS_EXPENSE);
        $netAccount = $this->accounts->findByCode(self::ACCOUNT_NET_PAYABLE);
        $pitAccount = $this->accounts->findByCode(self::ACCOUNT_PIT_PAYABLE);
        $pensionAccount = $this->accounts->findByCode(self::ACCOUNT_PENSION_PAYABLE);
        $healthAccount = $this->accounts->findByCode(self::ACCOUNT_HEALTH_PAYABLE);
        $employmentAccount = $this->accounts->findByCode(self::ACCOUNT_EMPLOYMENT_PAYABLE);

        if (!$grossAccount || !$netAccount || !$pitAccount || !$pensionAccount || !$healthAccount || !$employmentAccount) {
            throw new InvalidArgumentException('Стандардните сметки за плата не постојат во контниот план.');
        }

        $settings = $this->payroll->getSettings();

        $payslips = [];
        $totalGross = '0.00';
        $totalSeniority = '0.00';
        $totalSick = '0.00';
        $totalShift = '0.00';
        $totalHoliday = '0.00';
        $totalNet = '0.00';
        $totalPit = '0.00';
        $totalPension = '0.00';
        $totalHealth = '0.00';
        $totalEmployment = '0.00';

        foreach ($this->eligibleEmployeesForPeriod($periodDate) as $employee) {
            $base = $employee->baseGrossSalary;
            $seniorityMonths = $employee->priorStazMonths + $this->monthsAtCompany($employee->hireDate, $periodDate);
            $seniorityYears = (string) intdiv($seniorityMonths, 12);
            $seniority = bcmul($base, bcdiv(bcmul($seniorityYears, $settings->seniorityRatePerYear, 6), '100', 6), 2);
            $grossBeforeVariable = bcadd($base, $seniority, 2);

            $dailyRate = bcdiv($grossBeforeVariable, (string) $settings->dailyRateDivisor, 2);

            $input = $variableInputsByEmployeeId[$employee->id] ?? [];
            $sickDays = max(0, (int) ($input['sick_days'] ?? 0));
            $shiftDays = max(0, (int) ($input['shift_days'] ?? 0));
            $holidayDays = max(0, (int) ($input['holiday_days'] ?? 0));

            $sickCutRate = bcsub('100', $settings->sickLeavePayRate, 6); // % НЕисплатено за ден боледување
            $sickDeduction = bcmul($dailyRate, bcmul(bcdiv($sickCutRate, '100', 6), (string) $sickDays, 6), 2);
            $shiftSupplement = bcmul($dailyRate, bcmul(bcdiv($settings->shiftDayRate, '100', 6), (string) $shiftDays, 6), 2);
            $holidaySupplement = bcmul($dailyRate, bcmul(bcdiv($settings->holidayDayRate, '100', 6), (string) $holidayDays, 6), 2);

            $gross = bcadd(bcadd(bcsub($grossBeforeVariable, $sickDeduction, 2), $shiftSupplement, 2), $holidaySupplement, 2);
            if (bccomp($gross, '0.00', 2) < 0) {
                $gross = '0.00'; // одбитокот за боледување никогаш не смее да „преврти" бруто во негативно
            }

            $pension = bcmul($gross, bcdiv($settings->pensionRate, '100', 6), 2);
            $health = bcmul($gross, bcdiv($settings->healthRate, '100', 6), 2);
            $employment = bcmul($gross, bcdiv($settings->employmentRate, '100', 6), 2);
            $contributions = bcadd(bcadd($pension, $health, 2), $employment, 2);

            $taxableBase = bcsub(bcsub($gross, $contributions, 2), $settings->personalExemption, 2);
            if (bccomp($taxableBase, '0.00', 2) < 0) {
                $taxableBase = '0.00';
            }

            $pit = bcmul($taxableBase, bcdiv($settings->pitRate, '100', 6), 2);
            $net = bcsub(bcsub($gross, $contributions, 2), $pit, 2);

            $payslips[] = [
                'employee_id' => $employee->id,
                'base' => $base,
                'seniority_months' => $seniorityMonths,
                'seniority' => $seniority,
                'daily_rate' => $dailyRate,
                'sick_days' => $sickDays,
                'sick_deduction' => $sickDeduction,
                'shift_days' => $shiftDays,
                'shift_supplement' => $shiftSupplement,
                'holiday_days' => $holidayDays,
                'holiday_supplement' => $holidaySupplement,
                'gross' => $gross,
                'pension' => $pension,
                'health' => $health,
                'employment' => $employment,
                'taxable_base' => $taxableBase,
                'pit' => $pit,
                'net' => $net,
            ];

            $totalGross = bcadd($totalGross, $gross, 2);
            $totalSeniority = bcadd($totalSeniority, $seniority, 2);
            $totalSick = bcadd($totalSick, $sickDeduction, 2);
            $totalShift = bcadd($totalShift, $shiftSupplement, 2);
            $totalHoliday = bcadd($totalHoliday, $holidaySupplement, 2);
            $totalNet = bcadd($totalNet, $net, 2);
            $totalPit = bcadd($totalPit, $pit, 2);
            $totalPension = bcadd($totalPension, $pension, 2);
            $totalHealth = bcadd($totalHealth, $health, 2);
            $totalEmployment = bcadd($totalEmployment, $employment, 2);
        }

        if (!$payslips) {
            return ['count' => 0, 'total_gross' => '0.00', 'total_net' => '0.00'];
        }

        if (bccomp($totalGross, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('Вкупното бруто за периодот е 0 — проверете ги внесените денови боледување (одбитокот не смее да го надмине бруто износот).');
        }

        // Кредитните линии со износ 0.00 се испуштаат (на пр. персонален данок кога
        // сите вработени се под личното ослобување) — LedgerService одбива ставка
        // без дебит/кредит поголем од нула.
        $creditLines = [
            [$netAccount->id, $totalNet],
            [$pitAccount->id, $totalPit],
            [$pensionAccount->id, $totalPension],
            [$healthAccount->id, $totalHealth],
            [$employmentAccount->id, $totalEmployment],
        ];

        $lines = [['account_id' => $grossAccount->id, 'debit' => $totalGross, 'credit' => '0']];
        foreach ($creditLines as [$accountId, $amount]) {
            if (bccomp($amount, '0.00', 2) > 0) {
                $lines[] = ['account_id' => $accountId, 'debit' => '0', 'credit' => $amount];
            }
        }

        $entryId = $this->ledger->postEntry($periodDate, "Плата за период $periodDate", null, $lines);

        $runId = $this->payroll->createRun(new PayrollRun(
            $periodDate,
            $entryId,
            $totalGross,
            $totalSeniority,
            $totalSick,
            $totalShift,
            $totalHoliday,
            $totalNet,
            $totalPit,
            $totalPension,
            $totalHealth,
            $totalEmployment
        ));

        foreach ($payslips as $p) {
            $this->payroll->insertPayslip(new Payslip(
                $runId,
                $p['employee_id'],
                $p['base'],
                $p['seniority_months'],
                $p['seniority'],
                $p['daily_rate'],
                $p['sick_days'],
                $p['sick_deduction'],
                $p['shift_days'],
                $p['shift_supplement'],
                $p['holiday_days'],
                $p['holiday_supplement'],
                $p['gross'],
                $p['pension'],
                $p['health'],
                $p['employment'],
                $p['taxable_base'],
                $p['pit'],
                $p['net']
            ));
        }

        return ['count' => count($payslips), 'total_gross' => $totalGross, 'total_net' => $totalNet];
    }

    /** Завршени месеци стаж кај ОВАА фирма, hire_date -> периодот (пример: DateTime::diff го дава точниот календарски распад, вклучувајќи заокружени/делумни месеци на крајот). */
    private function monthsAtCompany(string $hireDate, string $periodDate): int
    {
        $interval = (new DateTime($hireDate))->diff(new DateTime($periodDate));

        return $interval->y * 12 + $interval->m;
    }
}
