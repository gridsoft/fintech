<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Payroll\Employee;
use App\Repository\EmployeeRepository;

class EmployeeController
{
    private EmployeeRepository $employees;

    public function __construct()
    {
        $this->employees = new EmployeeRepository();
    }

    public function index(Request $request): void
    {
        Response::view('employees/index', [
            'pageTitle' => 'Вработени',
            'activeNav' => 'employees',
            'breadcrumb' => ['Почетна' => '/', 'Вработени'],
            'employees' => $this->employees->all(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('employees/form', [
            'pageTitle' => 'Нов вработен',
            'activeNav' => 'employees',
            'breadcrumb' => ['Почетна' => '/', 'Вработени' => '/employees', 'Нов вработен'],
            'employee' => null,
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            Response::view('employees/form', [
                'pageTitle' => 'Нов вработен',
                'activeNav' => 'employees',
                'breadcrumb' => ['Почетна' => '/', 'Вработени' => '/employees', 'Нов вработен'],
                'employee' => null,
                'errors' => $errors,
            ]);
            return;
        }

        $this->employees->create(new Employee(
            trim($request->input('name')),
            $this->resolveEmbg($request),
            $request->input('hire_date'),
            $this->resolvePriorStazMonths($request),
            $this->resolveTerminationDate($request),
            number_format((float) $request->input('base_gross_salary'), 2, '.', ''),
            $request->input('is_active') === '1'
        ));

        Response::redirect('/employees');
    }

    public function edit(Request $request, string $id): void
    {
        $employee = $this->employees->find((int) $id);

        if (!$employee) {
            Response::html('<h1>404</h1><p>Вработениот не е пронајден.</p>', 404);
            return;
        }

        Response::view('employees/form', [
            'pageTitle' => 'Уреди вработен',
            'activeNav' => 'employees',
            'breadcrumb' => ['Почетна' => '/', 'Вработени' => '/employees', 'Уреди вработен'],
            'employee' => $employee,
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $employee = $this->employees->find((int) $id);

        if (!$employee) {
            Response::html('<h1>404</h1><p>Вработениот не е пронајден.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            Response::view('employees/form', [
                'pageTitle' => 'Уреди вработен',
                'activeNav' => 'employees',
                'breadcrumb' => ['Почетна' => '/', 'Вработени' => '/employees', 'Уреди вработен'],
                'employee' => $employee,
                'errors' => $errors,
            ]);
            return;
        }

        $employee->name = trim($request->input('name'));
        $employee->embg = $this->resolveEmbg($request);
        $employee->hireDate = $request->input('hire_date');
        $employee->priorStazMonths = $this->resolvePriorStazMonths($request);
        $employee->terminationDate = $this->resolveTerminationDate($request);
        $employee->baseGrossSalary = number_format((float) $request->input('base_gross_salary'), 2, '.', '');
        $employee->isActive = $request->input('is_active') === '1';

        $this->employees->update($employee);

        Response::redirect('/employees');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Името е задолжително.';
        }

        $hireDate = (string) $request->input('hire_date');
        if ($hireDate === '') {
            $errors['hire_date'] = 'Датумот на вработување е задолжителен.';
        }

        $terminationDate = trim((string) $request->input('termination_date'));
        if ($terminationDate !== '' && $hireDate !== '' && $terminationDate < $hireDate) {
            $errors['termination_date'] = 'Датумот на престанок не може да биде пред датумот на вработување.';
        }

        $salary = $request->input('base_gross_salary');
        if (!$salary || (float) $salary <= 0) {
            $errors['base_gross_salary'] = 'Внесете важечка бруто плата (поголема од нула).';
        }

        $embg = trim((string) $request->input('embg'));
        if ($embg !== '' && !preg_match('/^\d{13}$/', $embg)) {
            $errors['embg'] = 'ЕМБГ мора да содржи точно 13 цифри (или оставете празно).';
        }

        $stazYears = $request->input('prior_staz_years');
        if ($stazYears !== null && $stazYears !== '' && (int) $stazYears < 0) {
            $errors['prior_staz_years'] = 'Годините стаж не можат да бидат негативни.';
        }

        $stazMonths = $request->input('prior_staz_months');
        if ($stazMonths !== null && $stazMonths !== '' && ((int) $stazMonths < 0 || (int) $stazMonths > 11)) {
            $errors['prior_staz_months'] = 'Месеците стаж мора да бидат меѓу 0 и 11.';
        }

        return $errors;
    }

    private function resolveEmbg(Request $request): ?string
    {
        $embg = trim((string) $request->input('embg'));

        return $embg === '' ? null : $embg;
    }

    /** Признат стаж пред вработување, внесен на формата како одделни години+месеци, чуван како вкупен број месеци. */
    private function resolvePriorStazMonths(Request $request): int
    {
        $years = max(0, (int) $request->input('prior_staz_years', 0));
        $months = max(0, min(11, (int) $request->input('prior_staz_months', 0)));

        return $years * 12 + $months;
    }

    private function resolveTerminationDate(Request $request): ?string
    {
        $date = trim((string) $request->input('termination_date'));

        return $date === '' ? null : $date;
    }
}
