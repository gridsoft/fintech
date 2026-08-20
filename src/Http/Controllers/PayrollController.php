<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Payroll\PayrollSettings;
use App\Repository\EmployeeRepository;
use App\Repository\PayrollRepository;
use App\Service\PayrollService;
use InvalidArgumentException;

class PayrollController
{
    private PayrollRepository $payroll;
    private EmployeeRepository $employees;
    private PayrollService $service;

    public function __construct()
    {
        $this->payroll = new PayrollRepository();
        $this->employees = new EmployeeRepository();
        $this->service = new PayrollService($this->employees, $this->payroll);
    }

    public function index(Request $request): void
    {
        Response::view('payroll/index', [
            'pageTitle' => 'Плата',
            'activeNav' => 'payroll',
            'breadcrumb' => ['Почетна' => '/', 'Плата'],
            'runs' => $this->payroll->all(),
            'defaultPeriod' => date('Y-m'),
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $run = $this->payroll->find((int) $id);

        if (!$run) {
            Response::html('<h1>404</h1><p>Пресметката на плата не е пронајдена.</p>', 404);
            return;
        }

        $payslips = $this->payroll->payslipsForRun($run->id);
        $employeesById = [];
        foreach ($this->employees->all() as $employee) {
            $employeesById[$employee->id] = $employee;
        }

        Response::view('payroll/show', [
            'pageTitle' => 'Плата за период ' . $run->periodDate,
            'activeNav' => 'payroll',
            'breadcrumb' => ['Почетна' => '/', 'Плата' => '/payroll', $run->periodDate],
            'run' => $run,
            'payslips' => $payslips,
            'employeesById' => $employeesById,
        ]);
    }

    /** Подготвителен чекор — приказ на опфатените вработени + внес на месечните варијабли (боледување/смени/празници) пред вистинско извршување. */
    public function prepare(Request $request): void
    {
        $period = (string) $request->input('period');
        $periodDate = $this->resolvePeriodDate($period);

        if (!$periodDate) {
            Response::html('<h1>Грешка</h1><p>Изберете важечки период (месец).</p><p><a href="/payroll">Назад</a></p>', 422);
            return;
        }

        Response::view('payroll/prepare', [
            'pageTitle' => 'Подготовка на плата',
            'activeNav' => 'payroll',
            'breadcrumb' => ['Почетна' => '/', 'Плата' => '/payroll', 'Подготовка'],
            'period' => $period,
            'periodDate' => $periodDate,
            'employees' => $this->service->eligibleEmployeesForPeriod($periodDate),
            'settings' => $this->payroll->getSettings(),
            'errors' => [],
        ]);
    }

    public function runPayroll(Request $request): void
    {
        $period = (string) $request->input('period');
        $periodDate = $this->resolvePeriodDate($period);

        if (!$periodDate) {
            Response::html('<h1>Грешка</h1><p>Изберете важечки период (месец).</p><p><a href="/payroll">Назад</a></p>', 422);
            return;
        }

        [$variableInputs, $errors] = $this->resolveVariableInputs($request);

        if ($errors) {
            Response::view('payroll/prepare', [
                'pageTitle' => 'Подготовка на плата',
                'activeNav' => 'payroll',
                'breadcrumb' => ['Почетна' => '/', 'Плата' => '/payroll', 'Подготовка'],
                'period' => $period,
                'periodDate' => $periodDate,
                'employees' => $this->service->eligibleEmployeesForPeriod($periodDate),
                'settings' => $this->payroll->getSettings(),
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $this->service->runPayroll($periodDate, $variableInputs);
        } catch (InvalidArgumentException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/payroll">Назад</a></p>', 422);
            return;
        }

        Response::redirect('/payroll');
    }

    private function resolvePeriodDate(string $period): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            return null;
        }

        return date('Y-m-t', strtotime($period . '-01'));
    }

    /**
     * @return array{0: array<int, array{sick_days: int, shift_days: int, holiday_days: int}>, 1: array<string, string>}
     */
    private function resolveVariableInputs(Request $request): array
    {
        $days = $request->input('days', []);
        $variableInputs = [];
        $errors = [];
        $maxDays = 31;

        if (is_array($days)) {
            foreach ($days as $employeeId => $row) {
                $employeeId = (int) $employeeId;
                $sick = (int) ($row['sick'] ?? 0);
                $shift = (int) ($row['shift'] ?? 0);
                $holiday = (int) ($row['holiday'] ?? 0);

                if ($sick < 0 || $sick > $maxDays || $shift < 0 || $shift > $maxDays || $holiday < 0 || $holiday > $maxDays) {
                    $errors['days'] = 'Бројот на денови мора да биде помеѓу 0 и 31.';
                }

                $variableInputs[$employeeId] = ['sick_days' => $sick, 'shift_days' => $shift, 'holiday_days' => $holiday];
            }
        }

        return [$variableInputs, $errors];
    }

    public function settings(Request $request): void
    {
        Response::view('payroll/settings', [
            'pageTitle' => 'Поставки за плата',
            'activeNav' => 'payroll',
            'breadcrumb' => ['Почетна' => '/', 'Плата' => '/payroll', 'Поставки'],
            'settings' => $this->payroll->getSettings(),
            'errors' => [],
        ]);
    }

    public function updateSettings(Request $request): void
    {
        $settings = $this->payroll->getSettings();
        $errors = $this->validateSettings($request);

        if ($errors) {
            Response::view('payroll/settings', [
                'pageTitle' => 'Поставки за плата',
                'activeNav' => 'payroll',
                'breadcrumb' => ['Почетна' => '/', 'Плата' => '/payroll', 'Поставки'],
                'settings' => $settings,
                'errors' => $errors,
            ]);
            return;
        }

        $this->payroll->updateSettings(new PayrollSettings(
            number_format((float) $request->input('pension_rate'), 2, '.', ''),
            number_format((float) $request->input('health_rate'), 2, '.', ''),
            number_format((float) $request->input('employment_rate'), 2, '.', ''),
            number_format((float) $request->input('pit_rate'), 2, '.', ''),
            number_format((float) $request->input('seniority_rate_per_year'), 2, '.', ''),
            number_format((float) $request->input('sick_leave_pay_rate'), 2, '.', ''),
            number_format((float) $request->input('shift_day_rate'), 2, '.', ''),
            number_format((float) $request->input('holiday_day_rate'), 2, '.', ''),
            (int) $request->input('daily_rate_divisor'),
            number_format((float) $request->input('personal_exemption'), 2, '.', ''),
            $settings->id
        ));

        Response::redirect('/payroll/settings');
    }

    private function validateSettings(Request $request): array
    {
        $errors = [];

        foreach (['pension_rate', 'health_rate', 'employment_rate', 'pit_rate', 'seniority_rate_per_year', 'shift_day_rate', 'holiday_day_rate'] as $field) {
            $value = $request->input($field);
            if ($value === null || $value === '' || (float) $value < 0) {
                $errors[$field] = 'Внесете важечка стапка (%, 0 или поголема).';
            }
        }

        $sickRate = $request->input('sick_leave_pay_rate');
        if ($sickRate === null || $sickRate === '' || (float) $sickRate < 0 || (float) $sickRate > 100) {
            $errors['sick_leave_pay_rate'] = 'Внесете важечка стапка помеѓу 0 и 100%.';
        }

        $divisor = $request->input('daily_rate_divisor');
        if ($divisor === null || $divisor === '' || (int) $divisor < 1) {
            $errors['daily_rate_divisor'] = 'Внесете важечен делител (поголем од 0).';
        }

        $exemption = $request->input('personal_exemption');
        if ($exemption === null || $exemption === '' || (float) $exemption < 0) {
            $errors['personal_exemption'] = 'Внесете важечен износ на лично ослобување.';
        }

        return $errors;
    }
}
