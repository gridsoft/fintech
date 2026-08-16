<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Accounting\VatRate;
use App\Repository\AccountRepository;
use App\Repository\VatRateRepository;

class VatRateController
{
    private VatRateRepository $vatRates;
    private AccountRepository $accounts;

    public function __construct()
    {
        $this->vatRates = new VatRateRepository();
        $this->accounts = new AccountRepository();
    }

    public function index(Request $request): void
    {
        $accounts = $this->accounts->postable();

        Response::view('vat-rates/index', [
            'pageTitle' => 'ДДВ стапки',
            'activeNav' => 'vat-rates',
            'breadcrumb' => ['Почетна' => '/', 'ДДВ стапки'],
            'vatRates' => $this->vatRates->all(),
            'accountsById' => array_combine(array_map(fn ($a) => $a->id, $accounts), $accounts),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('vat-rates/form', [
            'pageTitle' => 'Нова ДДВ стапка',
            'activeNav' => 'vat-rates',
            'breadcrumb' => ['Почетна' => '/', 'ДДВ стапки' => '/vat-rates', 'Нова ставка'],
            'vatRate' => null,
            'accounts' => $this->accounts->postable(),
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            Response::view('vat-rates/form', [
                'pageTitle' => 'Нова ДДВ стапка',
                'activeNav' => 'vat-rates',
                'breadcrumb' => ['Почетна' => '/', 'ДДВ стапки' => '/vat-rates', 'Нова ставка'],
                'vatRate' => null,
                'accounts' => $this->accounts->postable(),
                'errors' => $errors,
            ]);
            return;
        }

        $payableAccountId = $request->input('payable_account_id');

        $vatRate = new VatRate(
            trim($request->input('name')),
            number_format((float) $request->input('rate'), 2, '.', ''),
            $request->input('type'),
            $payableAccountId !== '' ? (int) $payableAccountId : null,
            $request->input('is_active') === '1'
        );

        $this->vatRates->create($vatRate);

        Response::redirect('/vat-rates');
    }

    public function edit(Request $request, string $id): void
    {
        $vatRate = $this->vatRates->find((int) $id);

        if (!$vatRate) {
            Response::html('<h1>404</h1><p>ДДВ стапката не е пронајдена.</p>', 404);
            return;
        }

        Response::view('vat-rates/form', [
            'pageTitle' => 'Уреди ДДВ стапка',
            'activeNav' => 'vat-rates',
            'breadcrumb' => ['Почетна' => '/', 'ДДВ стапки' => '/vat-rates', 'Уреди ставка'],
            'vatRate' => $vatRate,
            'accounts' => $this->accounts->postable(),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $vatRate = $this->vatRates->find((int) $id);

        if (!$vatRate) {
            Response::html('<h1>404</h1><p>ДДВ стапката не е пронајдена.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            Response::view('vat-rates/form', [
                'pageTitle' => 'Уреди ДДВ стапка',
                'activeNav' => 'vat-rates',
                'breadcrumb' => ['Почетна' => '/', 'ДДВ стапки' => '/vat-rates', 'Уреди ставка'],
                'vatRate' => $vatRate,
                'accounts' => $this->accounts->postable(),
                'errors' => $errors,
            ]);
            return;
        }

        $payableAccountId = $request->input('payable_account_id');

        $vatRate->name = trim($request->input('name'));
        $vatRate->rate = number_format((float) $request->input('rate'), 2, '.', '');
        $vatRate->type = $request->input('type');
        $vatRate->payableAccountId = $payableAccountId !== '' ? (int) $payableAccountId : null;
        $vatRate->isActive = $request->input('is_active') === '1';

        $this->vatRates->update($vatRate);

        Response::redirect('/vat-rates');
    }

    public function destroy(Request $request, string $id): void
    {
        $vatRateId = (int) $id;

        if ($this->vatRates->isInUse($vatRateId)) {
            Response::html('<h1>Грешка</h1><p>Оваа ДДВ стапка се користи од категорија или фактура и не може да се избрише. Деактивирај ја наместо тоа.</p><p><a href="/vat-rates">Назад</a></p>', 422);
            return;
        }

        $this->vatRates->delete($vatRateId);
        Response::redirect('/vat-rates');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        if ($request->input('rate') === null || $request->input('rate') === '') {
            $errors['rate'] = 'Стапката е задолжителна.';
        } elseif ((float) $request->input('rate') < 0) {
            $errors['rate'] = 'Стапката не може да биде негативна.';
        }

        if (!in_array($request->input('type'), VatRate::TYPES, true)) {
            $errors['type'] = 'Изберете важечки тип.';
        }

        return $errors;
    }
}
