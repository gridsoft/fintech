<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Accounting\Terk;
use App\Domain\Accounting\TerkLine;
use App\Repository\AccountRepository;
use App\Repository\TerkRepository;

class TerkController
{
    private TerkRepository $terkovi;
    private AccountRepository $accounts;

    public function __construct()
    {
        $this->terkovi = new TerkRepository();
        $this->accounts = new AccountRepository();
    }

    public function index(Request $request): void
    {
        Response::view('terkovi/index', [
            'pageTitle' => 'Теркови',
            'activeNav' => 'terkovi',
            'breadcrumb' => ['Почетна' => '/', 'Теркови'],
            'terkovi' => $this->terkovi->all(),
            'lineCounts' => $this->terkovi->lineCounts(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('terkovi/form', [
            'pageTitle' => 'Нов терк',
            'activeNav' => 'terkovi',
            'breadcrumb' => ['Почетна' => '/', 'Теркови' => '/terkovi', 'Нов терк'],
            'terk' => null,
            'accounts' => $this->accounts->all(),
            'errors' => [],
            'old' => [
                'name' => '',
                'description' => '',
                'lines' => [
                    ['account_id' => '', 'side' => 'debit', 'amount_source' => 'gross', 'tag_partner' => '1'],
                    ['account_id' => '', 'side' => 'credit', 'amount_source' => 'net', 'tag_partner' => ''],
                ],
            ],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm(null, $errors, $this->linesFromRequest($request), $request);
            return;
        }

        $terk = new Terk(trim($request->input('name')), trim((string) $request->input('description')) ?: null, true);
        $terkId = $this->terkovi->create($terk);

        $this->saveLines($terkId, $this->linesFromRequest($request));

        Response::redirect('/terkovi');
    }

    public function edit(Request $request, string $id): void
    {
        $terk = $this->terkovi->find((int) $id);

        if (!$terk) {
            Response::html('<h1>404</h1><p>Теркот не е пронајден.</p>', 404);
            return;
        }

        Response::view('terkovi/form', [
            'pageTitle' => 'Уреди терк',
            'activeNav' => 'terkovi',
            'breadcrumb' => ['Почетна' => '/', 'Теркови' => '/terkovi', 'Уреди терк'],
            'terk' => $terk,
            'accounts' => $this->accounts->all(),
            'errors' => [],
            'old' => [
                'name' => $terk->name,
                'description' => $terk->description ?? '',
                'lines' => array_map(fn (TerkLine $l) => [
                    'account_id' => (string) $l->accountId,
                    'side' => $l->side,
                    'amount_source' => $l->amountSource,
                    'tag_partner' => $l->tagPartner ? '1' : '',
                ], $terk->lines),
            ],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $terk = $this->terkovi->find((int) $id);

        if (!$terk) {
            Response::html('<h1>404</h1><p>Теркот не е пронајден.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm($terk, $errors, $this->linesFromRequest($request), $request);
            return;
        }

        $terk->name = trim($request->input('name'));
        $terk->description = trim((string) $request->input('description')) ?: null;
        $this->terkovi->update($terk);

        $this->saveLines($terk->id, $this->linesFromRequest($request));

        Response::redirect('/terkovi');
    }

    public function destroy(Request $request, string $id): void
    {
        $terkId = (int) $id;

        if ($this->terkovi->isUsedByInvoice($terkId)) {
            Response::html('<h1>Грешка</h1><p>Овој терк веќе е користен за книжење фактура и не може да се избрише.</p><p><a href="/terkovi">Назад</a></p>', 422);
            return;
        }

        $this->terkovi->delete($terkId);
        Response::redirect('/terkovi');
    }

    private function renderForm(?Terk $terk, array $errors, array $lines, Request $request): void
    {
        Response::view('terkovi/form', [
            'pageTitle' => $terk ? 'Уреди терк' : 'Нов терк',
            'activeNav' => 'terkovi',
            'breadcrumb' => ['Почетна' => '/', 'Теркови' => '/terkovi', $terk ? 'Уреди терк' : 'Нов терк'],
            'terk' => $terk,
            'accounts' => $this->accounts->all(),
            'errors' => $errors,
            'old' => [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'lines' => $lines,
            ],
        ]);
    }

    private function linesFromRequest(Request $request): array
    {
        $accountIds = $request->input('line_account_id', []);
        $sides = $request->input('line_side', []);
        $sources = $request->input('line_amount_source', []);
        $tagPartners = $request->input('line_tag_partner', []);

        $lines = [];
        foreach ($accountIds as $i => $accountId) {
            if ($accountId === '') {
                continue;
            }

            $lines[] = [
                'account_id' => $accountId,
                'side' => $sides[$i] ?? 'debit',
                'amount_source' => $sources[$i] ?? 'net',
                'tag_partner' => in_array((string) $i, $tagPartners, true) ? '1' : '',
            ];
        }

        return $lines;
    }

    private function saveLines(int $terkId, array $lines): void
    {
        $this->terkovi->deleteLines($terkId);

        foreach ($lines as $i => $line) {
            $this->terkovi->insertLine(new TerkLine(
                (int) $line['account_id'],
                in_array($line['side'], TerkLine::SIDES, true) ? $line['side'] : 'debit',
                in_array($line['amount_source'], TerkLine::SOURCES, true) ? $line['amount_source'] : 'net',
                $line['tag_partner'] === '1',
                $i,
                $terkId
            ));
        }
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        $lines = $this->linesFromRequest($request);

        if (count($lines) < 2) {
            $errors['lines'] = 'Теркот мора да содржи барем 2 ставки (за да може да се балансира книжењето).';
        }

        return $errors;
    }
}
