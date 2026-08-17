<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Partners\Partner;
use App\Domain\Partners\PartnerEmployee;
use App\Repository\JournalRepository;
use App\Repository\PartnerRepository;
use App\Service\PartnerService;
use InvalidArgumentException;

class PartnerController
{
    private PartnerRepository $partners;
    private JournalRepository $journal;
    private PartnerService $service;

    public function __construct()
    {
        $this->partners = new PartnerRepository();
        $this->journal = new JournalRepository();
        $this->service = new PartnerService($this->partners);
    }

    public function index(Request $request): void
    {
        Response::view('partners/index', [
            'pageTitle' => 'Партнери',
            'activeNav' => 'partners',
            'breadcrumb' => ['Почетна' => '/', 'Партнери'],
            'partners' => $this->partners->all(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('partners/form', [
            'pageTitle' => 'Нов партнер',
            'activeNav' => 'partners',
            'breadcrumb' => ['Почетна' => '/', 'Партнери' => '/partners', 'Нов партнер'],
            'partner' => null,
            'employees' => [],
            'customFields' => [],
            'oldEmployees' => [],
            'oldCustomFields' => [],
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);
        $employeeRows = $this->collectEmployeeRows($request);
        $customFieldRows = $this->collectCustomFieldRows($request);

        if (!$errors) {
            $partnerId = $this->service->createPartner(
                $this->partnerFromRequest($request),
                $employeeRows,
                $customFieldRows
            );

            Response::redirect("/partners/$partnerId/edit");
            return;
        }

        Response::view('partners/form', [
            'pageTitle' => 'Нов партнер',
            'activeNav' => 'partners',
            'breadcrumb' => ['Почетна' => '/', 'Партнери' => '/partners', 'Нов партнер'],
            'partner' => null,
            'employees' => [],
            'customFields' => [],
            'oldEmployees' => $employeeRows,
            'oldCustomFields' => $customFieldRows,
            'errors' => $errors,
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $partner = $this->partners->find((int) $id);

        if (!$partner) {
            Response::html('<h1>404</h1><p>Партнерот не е пронајден.</p>', 404);
            return;
        }

        Response::view('partners/form', [
            'pageTitle' => 'Уреди партнер',
            'activeNav' => 'partners',
            'breadcrumb' => ['Почетна' => '/', 'Партнери' => '/partners', 'Уреди партнер'],
            'partner' => $partner,
            'employees' => $this->partners->employeesFor($partner->id),
            'customFields' => $this->partners->customFieldsFor($partner->id),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $partner = $this->partners->find((int) $id);

        if (!$partner) {
            Response::html('<h1>404</h1><p>Партнерот не е пронајден.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            Response::view('partners/form', [
                'pageTitle' => 'Уреди партнер',
                'activeNav' => 'partners',
                'breadcrumb' => ['Почетна' => '/', 'Партнери' => '/partners', 'Уреди партнер'],
                'partner' => $partner,
                'employees' => $this->partners->employeesFor($partner->id),
                'customFields' => $this->partners->customFieldsFor($partner->id),
                'errors' => $errors,
            ]);
            return;
        }

        $updated = $this->partnerFromRequest($request, $partner->id);
        $this->partners->update($updated);

        Response::redirect("/partners/{$partner->id}/edit");
    }

    public function destroy(Request $request, string $id): void
    {
        $this->partners->delete((int) $id);
        Response::redirect('/partners');
    }

    public function addEmployee(Request $request, string $id): void
    {
        $partnerId = (int) $id;
        $name = trim((string) $request->input('name'));

        if ($name === '') {
            Response::html('<h1>Грешка</h1><p>Името е задолжително.</p><p><a href="/partners/' . $partnerId . '/edit">Назад</a></p>', 422);
            return;
        }

        $this->partners->addEmployee(new PartnerEmployee(
            $partnerId,
            $name,
            trim((string) $request->input('job_title')) ?: null,
            trim((string) $request->input('phone')) ?: null,
            trim((string) $request->input('email')) ?: null
        ));

        Response::redirect("/partners/$partnerId/edit");
    }

    public function deleteEmployee(Request $request, string $id, string $employeeId): void
    {
        $this->partners->deleteEmployee((int) $employeeId, (int) $id);
        Response::redirect("/partners/$id/edit");
    }

    public function addCustomField(Request $request, string $id): void
    {
        $partnerId = (int) $id;
        $key = trim((string) $request->input('field_key'));
        $value = trim((string) $request->input('field_value'));

        if ($key === '') {
            Response::html('<h1>Грешка</h1><p>Називот на полето е задолжителен.</p><p><a href="/partners/' . $partnerId . '/edit">Назад</a></p>', 422);
            return;
        }

        $this->partners->setCustomField($partnerId, $key, $value !== '' ? $value : null);

        Response::redirect("/partners/$partnerId/edit");
    }

    public function deleteCustomField(Request $request, string $id, string $fieldId): void
    {
        $this->partners->deleteCustomField((int) $fieldId, (int) $id);
        Response::redirect("/partners/$id/edit");
    }

    public function statement(Request $request, string $id): void
    {
        $partner = $this->partners->find((int) $id);

        if (!$partner) {
            Response::html('<h1>404</h1><p>Партнерот не е пронајден.</p>', 404);
            return;
        }

        $rows = [];
        $balance = 0.0;

        foreach ($this->journal->linesForPartner($partner->id) as $item) {
            $debit = (float) $item['line']->debit;
            $credit = (float) $item['line']->credit;
            $balance += $debit - $credit;

            $rows[] = [
                'entry' => $item['entry'],
                'line' => $item['line'],
                'account_code' => $item['account_code'],
                'account_name' => $item['account_name'],
                'balance' => $balance,
            ];
        }

        Response::view('partners/statement', [
            'pageTitle' => 'Картица на партнер',
            'activeNav' => 'partners',
            'breadcrumb' => ['Почетна' => '/', 'Партнери' => '/partners', $partner->name],
            'partner' => $partner,
            'rows' => $rows,
            'closingBalance' => $balance,
        ]);
    }

    /** @return array<int, array{name: string, job_title: ?string, phone: ?string, email: ?string}> */
    private function collectEmployeeRows(Request $request): array
    {
        $names = $request->input('employee_name', []);
        $jobTitles = $request->input('employee_job_title', []);
        $phones = $request->input('employee_phone', []);
        $emails = $request->input('employee_email', []);

        $rows = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'job_title' => trim((string) ($jobTitles[$i] ?? '')) ?: null,
                'phone' => trim((string) ($phones[$i] ?? '')) ?: null,
                'email' => trim((string) ($emails[$i] ?? '')) ?: null,
            ];
        }

        return $rows;
    }

    /** @return array<int, array{key: string, value: ?string}> */
    private function collectCustomFieldRows(Request $request): array
    {
        $keys = $request->input('custom_field_key', []);
        $values = $request->input('custom_field_value', []);

        $rows = [];
        foreach ($keys as $i => $key) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'value' => trim((string) ($values[$i] ?? '')) ?: null,
            ];
        }

        return $rows;
    }

    private function partnerFromRequest(Request $request, ?int $id = null): Partner
    {
        return new Partner(
            trim($request->input('name')),
            $request->input('type'),
            $this->nullableInput($request, 'tax_number'),
            $this->nullableInput($request, 'address_line1'),
            $this->nullableInput($request, 'address_line2'),
            $this->nullableInput($request, 'postal_code'),
            $this->nullableInput($request, 'city'),
            strtoupper(trim((string) $request->input('country'))) ?: 'MK',
            $this->nullableInput($request, 'phone'),
            $this->nullableInput($request, 'fax'),
            $this->nullableInput($request, 'mobile'),
            $this->nullableInput($request, 'email'),
            $this->nullableInput($request, 'website'),
            $this->nullableInput($request, 'bank_account'),
            $this->nullableInput($request, 'vat_number'),
            $this->nullableInput($request, 'iban'),
            $this->nullableInput($request, 'swift'),
            $this->nullableInput($request, 'timocom_id'),
            $request->input('is_active') === '1',
            $id
        );
    }

    private function nullableInput(Request $request, string $key): ?string
    {
        $value = trim((string) $request->input($key));

        return $value !== '' ? $value : null;
    }

    private function validate(Request $request): array
    {
        $errors = [];

        $name = trim((string) $request->input('name'));
        $type = $request->input('type');

        if ($name === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        if (!in_array($type, Partner::TYPES, true)) {
            $errors['type'] = 'Изберете важечки тип на партнер.';
        }

        return $errors;
    }
}
