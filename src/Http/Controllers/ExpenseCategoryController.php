<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Invoicing\ExpenseCategory;
use App\Repository\AccountRepository;
use App\Repository\ExpenseCategoryRepository;

class ExpenseCategoryController
{
    private ExpenseCategoryRepository $categories;
    private AccountRepository $accounts;

    public function __construct()
    {
        $this->categories = new ExpenseCategoryRepository();
        $this->accounts = new AccountRepository();
    }

    public function index(Request $request): void
    {
        Response::view('expense-categories/index', [
            'pageTitle' => 'Категории на трошоци',
            'activeNav' => 'expense-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на трошоци'],
            'categories' => $this->categories->all(),
            'accountsById' => $this->accountsById(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('expense-categories/form', [
            'pageTitle' => 'Нова категорија',
            'activeNav' => 'expense-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на трошоци' => '/expense-categories', 'Нова категорија'],
            'category' => null,
            'accounts' => $this->accounts->postable(),
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
        $defaultUsefulLifeMonths = $request->input('default_useful_life_months');

        $category = new ExpenseCategory(
            trim($request->input('name')),
            (int) $request->input('domestic_account_id'),
            $foreignAccountId !== '' ? (int) $foreignAccountId : null,
            $request->input('vat_deductible'),
            $request->input('is_capitalizable') === '1',
            $defaultUsefulLifeMonths !== '' && $defaultUsefulLifeMonths !== null ? (int) $defaultUsefulLifeMonths : null,
            $request->input('reverse_charge_applicable') === '1',
            $request->input('is_active') === '1'
        );

        $this->categories->create($category);

        Response::redirect('/expense-categories');
    }

    public function edit(Request $request, string $id): void
    {
        $category = $this->categories->find((int) $id);

        if (!$category) {
            Response::html('<h1>404</h1><p>Категоријата не е пронајдена.</p>', 404);
            return;
        }

        Response::view('expense-categories/form', [
            'pageTitle' => 'Уреди категорија',
            'activeNav' => 'expense-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на трошоци' => '/expense-categories', 'Уреди категорија'],
            'category' => $category,
            'accounts' => $this->accounts->postable(),
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
        $defaultUsefulLifeMonths = $request->input('default_useful_life_months');

        $category->name = trim($request->input('name'));
        $category->domesticAccountId = (int) $request->input('domestic_account_id');
        $category->foreignAccountId = $foreignAccountId !== '' ? (int) $foreignAccountId : null;
        $category->vatDeductible = $request->input('vat_deductible');
        $category->isCapitalizable = $request->input('is_capitalizable') === '1';
        $category->defaultUsefulLifeMonths = $defaultUsefulLifeMonths !== '' && $defaultUsefulLifeMonths !== null ? (int) $defaultUsefulLifeMonths : null;
        $category->reverseChargeApplicable = $request->input('reverse_charge_applicable') === '1';
        $category->isActive = $request->input('is_active') === '1';

        $this->categories->update($category);

        Response::redirect('/expense-categories');
    }

    public function destroy(Request $request, string $id): void
    {
        $categoryId = (int) $id;

        if ($this->categories->hasPurchaseLines($categoryId)) {
            Response::html('<h1>Грешка</h1><p>Оваа категорија се користи на влезни фактури и не може да се избрише.</p><p><a href="/expense-categories">Назад</a></p>', 422);
            return;
        }

        $this->categories->delete($categoryId);
        Response::redirect('/expense-categories');
    }

    private function renderForm(?ExpenseCategory $category, array $errors): void
    {
        Response::view('expense-categories/form', [
            'pageTitle' => $category ? 'Уреди категорија' : 'Нова категорија',
            'activeNav' => 'expense-categories',
            'breadcrumb' => ['Почетна' => '/', 'Категории на трошоци' => '/expense-categories', $category ? 'Уреди категорија' : 'Нова категорија'],
            'category' => $category,
            'accounts' => $this->accounts->postable(),
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

        if (!in_array($request->input('vat_deductible'), ExpenseCategory::VAT_DEDUCTIBLE_OPTIONS, true)) {
            $errors['vat_deductible'] = 'Изберете важечка одбивност на ДДВ.';
        }

        if ($request->input('is_capitalizable') === '1') {
            $defaultUsefulLifeMonths = $request->input('default_useful_life_months');

            if ($defaultUsefulLifeMonths === '' || $defaultUsefulLifeMonths === null || (int) $defaultUsefulLifeMonths <= 0) {
                $errors['default_useful_life_months'] = 'Внесете амортизациски век (месеци) за основно средство.';
            }
        }

        return $errors;
    }

    /** @return array<int, \App\Domain\Accounting\Account> */
    private function accountsById(): array
    {
        $accounts = $this->accounts->postable();

        return array_combine(array_map(fn ($a) => $a->id, $accounts), $accounts);
    }
}
