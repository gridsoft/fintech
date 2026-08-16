<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Partners\Partner;
use App\Repository\JournalRepository;
use App\Repository\PartnerRepository;

class PartnerController
{
    private PartnerRepository $partners;
    private JournalRepository $journal;

    public function __construct()
    {
        $this->partners = new PartnerRepository();
        $this->journal = new JournalRepository();
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
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            Response::view('partners/form', [
                'pageTitle' => 'Нов партнер',
                'activeNav' => 'partners',
                'breadcrumb' => ['Почетна' => '/', 'Партнери' => '/partners', 'Нов партнер'],
                'partner' => null,
                'errors' => $errors,
            ]);
            return;
        }

        $partner = new Partner(
            trim($request->input('name')),
            $request->input('type'),
            trim((string) $request->input('tax_number')) ?: null,
            trim((string) $request->input('address')) ?: null,
            trim((string) $request->input('contact')) ?: null,
            strtoupper(trim((string) $request->input('country'))) ?: 'MK',
            $request->input('is_active') === '1'
        );

        $this->partners->create($partner);

        Response::redirect('/partners');
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
                'errors' => $errors,
            ]);
            return;
        }

        $partner->name = trim($request->input('name'));
        $partner->type = $request->input('type');
        $partner->taxNumber = trim((string) $request->input('tax_number')) ?: null;
        $partner->address = trim((string) $request->input('address')) ?: null;
        $partner->contact = trim((string) $request->input('contact')) ?: null;
        $partner->country = strtoupper(trim((string) $request->input('country'))) ?: 'MK';
        $partner->isActive = $request->input('is_active') === '1';

        $this->partners->update($partner);

        Response::redirect('/partners');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->partners->delete((int) $id);
        Response::redirect('/partners');
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
