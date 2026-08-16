<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Accounting\Account;
use App\Repository\AccountRepository;
use App\Repository\JournalRepository;

class AccountController
{
    private AccountRepository $accounts;
    private JournalRepository $journal;

    public function __construct()
    {
        $this->accounts = new AccountRepository();
        $this->journal = new JournalRepository();
    }

    public function index(Request $request): void
    {
        $accounts = $this->accounts->all();

        Response::view('accounts/index', [
            'pageTitle' => 'Контен план',
            'activeNav' => 'accounts',
            'breadcrumb' => ['Почетна' => '/', 'Контен план'],
            'accounts' => $accounts,
            'accountsById' => array_combine(
                array_map(fn ($a) => $a->id, $accounts),
                $accounts
            ),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('accounts/form', [
            'pageTitle' => 'Нова сметка',
            'activeNav' => 'accounts',
            'breadcrumb' => ['Почетна' => '/', 'Контен план' => '/accounts', 'Нова сметка'],
            'account' => null,
            'parentOptions' => $this->accounts->options(),
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            Response::view('accounts/form', [
                'pageTitle' => 'Нова сметка',
                'activeNav' => 'accounts',
                'breadcrumb' => ['Почетна' => '/', 'Контен план' => '/accounts', 'Нова сметка'],
                'account' => null,
                'parentOptions' => $this->accounts->options(),
                'errors' => $errors,
            ]);
            return;
        }

        $parentId = $request->input('parent_id');

        $account = new Account(
            trim($request->input('code')),
            trim($request->input('name')),
            $request->input('type'),
            $parentId !== '' ? (int) $parentId : null,
            $request->input('is_active') === '1'
        );

        $this->accounts->create($account);

        Response::redirect('/accounts');
    }

    public function edit(Request $request, string $id): void
    {
        $account = $this->accounts->find((int) $id);

        if (!$account) {
            Response::html('<h1>404</h1><p>Сметката не е пронајдена.</p>', 404);
            return;
        }

        Response::view('accounts/form', [
            'pageTitle' => 'Уреди сметка',
            'activeNav' => 'accounts',
            'breadcrumb' => ['Почетна' => '/', 'Контен план' => '/accounts', 'Уреди сметка'],
            'account' => $account,
            'parentOptions' => $this->accounts->options($account->id),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $account = $this->accounts->find((int) $id);

        if (!$account) {
            Response::html('<h1>404</h1><p>Сметката не е пронајдена.</p>', 404);
            return;
        }

        $errors = $this->validate($request, $account->id);

        if ($errors) {
            Response::view('accounts/form', [
                'pageTitle' => 'Уреди сметка',
                'activeNav' => 'accounts',
                'breadcrumb' => ['Почетна' => '/', 'Контен план' => '/accounts', 'Уреди сметка'],
                'account' => $account,
                'parentOptions' => $this->accounts->options($account->id),
                'errors' => $errors,
            ]);
            return;
        }

        $parentId = $request->input('parent_id');

        $account->code = trim($request->input('code'));
        $account->name = trim($request->input('name'));
        $account->type = $request->input('type');
        $account->parentId = $parentId !== '' ? (int) $parentId : null;
        $account->isActive = $request->input('is_active') === '1';

        $this->accounts->update($account);

        Response::redirect('/accounts');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->accounts->delete((int) $id);
        Response::redirect('/accounts');
    }

    public function ledger(Request $request, string $id): void
    {
        $account = $this->accounts->find((int) $id);

        if (!$account) {
            Response::html('<h1>404</h1><p>Сметката не е пронајдена.</p>', 404);
            return;
        }

        $debitNormal = in_array($account->type, ['asset', 'expense'], true);
        $rows = [];
        $balance = 0.0;

        foreach ($this->journal->linesForAccount($account->id) as $item) {
            $debit = (float) $item['line']->debit;
            $credit = (float) $item['line']->credit;
            $balance += $debitNormal ? ($debit - $credit) : ($credit - $debit);

            $rows[] = [
                'entry' => $item['entry'],
                'line' => $item['line'],
                'balance' => $balance,
            ];
        }

        Response::view('accounts/ledger', [
            'pageTitle' => 'Картица на сметка',
            'activeNav' => 'accounts',
            'breadcrumb' => ['Почетна' => '/', 'Контен план' => '/accounts', $account->code . ' — ' . $account->name],
            'account' => $account,
            'rows' => $rows,
            'closingBalance' => $balance,
        ]);
    }

    private function validate(Request $request, ?int $excludeId = null): array
    {
        $errors = [];

        $code = trim((string) $request->input('code'));
        $name = trim((string) $request->input('name'));
        $type = $request->input('type');
        $parentId = $request->input('parent_id');

        if ($code === '') {
            $errors['code'] = 'Шифрата е задолжителна.';
        } elseif ($this->accounts->codeExists($code, $excludeId)) {
            $errors['code'] = 'Веќе постои сметка со оваа шифра.';
        }

        if ($name === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        if (!in_array($type, Account::TYPES, true)) {
            $errors['type'] = 'Изберете важечки тип на сметка.';
        }

        if ($parentId !== '' && $parentId !== null && (int) $parentId === $excludeId) {
            $errors['parent_id'] = 'Сметката не може да биде родител сама на себе.';
        }

        return $errors;
    }
}
