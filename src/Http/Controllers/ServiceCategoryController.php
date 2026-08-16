<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Invoicing\ServiceCategory;
use App\Repository\AccountRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\VatRateRepository;

class ServiceCategoryController
{
    private ServiceCategoryRepository $categories;
    private AccountRepository $accounts;
    private VatRateRepository $vatRates;

    public function __construct()
    {
        $this->categories = new ServiceCategoryRepository();
        $this->accounts = new AccountRepository();
        $this->vatRates = new VatRateRepository();
    }

    public function index(Request $request): void
    {
        Response::view('service-categories/index', [
            'pageTitle' => 'Категории на услуги',
            'activeNav' => 'service-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на услуги'],
            'categories' => $this->categories->all(),
            'accountsById' => $this->accountsById(),
            'vatRatesById' => $this->vatRatesById(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('service-categories/form', [
            'pageTitle' => 'Нова категорија',
            'activeNav' => 'service-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на услуги' => '/service-categories', 'Нова категорија'],
            'category' => null,
            'accounts' => $this->accounts->postable(),
            'vatRates' => $this->vatRates->allActive(),
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm(null, $errors);
            return;
        }

        $foreignAccountId = $request->input('foreign_account_id');
        $foreignVatRateId = $request->input('foreign_vat_rate_id');

        $category = new ServiceCategory(
            trim($request->input('name')),
            (int) $request->input('domestic_account_id'),
            (int) $request->input('domestic_vat_rate_id'),
            $foreignAccountId !== '' ? (int) $foreignAccountId : null,
            $foreignVatRateId !== '' ? (int) $foreignVatRateId : null,
            $request->input('is_active') === '1'
        );

        $this->categories->create($category);

        Response::redirect('/service-categories');
    }

    public function edit(Request $request, string $id): void
    {
        $category = $this->categories->find((int) $id);

        if (!$category) {
            Response::html('<h1>404</h1><p>Категоријата не е пронајдена.</p>', 404);
            return;
        }

        Response::view('service-categories/form', [
            'pageTitle' => 'Уреди категорија',
            'activeNav' => 'service-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на услуги' => '/service-categories', 'Уреди категорија'],
            'category' => $category,
            'accounts' => $this->accounts->postable(),
            'vatRates' => $this->vatRates->allActive(),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $category = $this->categories->find((int) $id);

        if (!$category) {
            Response::html('<h1>404</h1><p>Категоријата не е пронајдена.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm($category, $errors);
            return;
        }

        $foreignAccountId = $request->input('foreign_account_id');
        $foreignVatRateId = $request->input('foreign_vat_rate_id');

        $category->name = trim($request->input('name'));
        $category->domesticAccountId = (int) $request->input('domestic_account_id');
        $category->domesticVatRateId = (int) $request->input('domestic_vat_rate_id');
        $category->foreignAccountId = $foreignAccountId !== '' ? (int) $foreignAccountId : null;
        $category->foreignVatRateId = $foreignVatRateId !== '' ? (int) $foreignVatRateId : null;
        $category->isActive = $request->input('is_active') === '1';

        $this->categories->update($category);

        Response::redirect('/service-categories');
    }

    public function destroy(Request $request, string $id): void
    {
        $categoryId = (int) $id;

        if ($this->categories->hasServices($categoryId)) {
            Response::html('<h1>Грешка</h1><p>Оваа категорија содржи услуги и не може да се избрише.</p><p><a href="/service-categories">Назад</a></p>', 422);
            return;
        }

        $this->categories->delete($categoryId);
        Response::redirect('/service-categories');
    }

    private function renderForm(?ServiceCategory $category, array $errors): void
    {
        Response::view('service-categories/form', [
            'pageTitle' => $category ? 'Уреди категорија' : 'Нова категорија',
            'activeNav' => 'service-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на услуги' => '/service-categories', $category ? 'Уреди категорија' : 'Нова категорија'],
            'category' => $category,
            'accounts' => $this->accounts->postable(),
            'vatRates' => $this->vatRates->allActive(),
            'errors' => $errors,
        ]);
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        if (!$request->input('domestic_account_id')) {
            $errors['domestic_account_id'] = 'Изберете сметка за домашен промет.';
        }

        if (!$request->input('domestic_vat_rate_id')) {
            $errors['domestic_vat_rate_id'] = 'Изберете ДДВ стапка за домашен промет.';
        }

        return $errors;
    }

    /** @return array<int, \App\Domain\Accounting\Account> */
    private function accountsById(): array
    {
        $accounts = $this->accounts->postable();

        return array_combine(array_map(fn ($a) => $a->id, $accounts), $accounts);
    }

    /** @return array<int, \App\Domain\Accounting\VatRate> */
    private function vatRatesById(): array
    {
        $vatRates = $this->vatRates->all();

        return array_combine(array_map(fn ($v) => $v->id, $vatRates), $vatRates);
    }
}
