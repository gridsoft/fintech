<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AccountRepository;
use App\Repository\JournalRepository;
use App\Repository\PartnerRepository;
use App\Service\LedgerService;
use InvalidArgumentException;
use RuntimeException;

class JournalController
{
    private JournalRepository $journal;
    private AccountRepository $accounts;
    private PartnerRepository $partners;
    private LedgerService $ledger;

    public function __construct()
    {
        $this->journal = new JournalRepository();
        $this->accounts = new AccountRepository();
        $this->partners = new PartnerRepository();
        $this->ledger = new LedgerService($this->journal);
    }

    public function index(Request $request): void
    {
        Response::view('journal/index', [
            'pageTitle' => 'Дневник',
            'activeNav' => 'journal',
            'breadcrumb' => ['Почетна' => '/', 'Дневник'],
            'entries' => $this->journal->allWithTotals(),
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $entry = $this->journal->find((int) $id);

        if (!$entry) {
            Response::html('<h1>404</h1><p>Записот не е пронајден.</p>', 404);
            return;
        }

        Response::view('journal/show', [
            'pageTitle' => 'Преглед на запис',
            'activeNav' => 'journal',
            'breadcrumb' => ['Почетна' => '/', 'Дневник' => '/journal', 'Преглед'],
            'entry' => $entry,
            'accountsById' => $this->accountsById(),
            'partnersById' => $this->partnersById(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('journal/form', [
            'pageTitle' => 'Нов запис',
            'activeNav' => 'journal',
            'breadcrumb' => ['Почетна' => '/', 'Дневник' => '/journal', 'Нов запис'],
            'accounts' => $this->accounts->all(),
            'partners' => $this->partners->all(),
            'errors' => [],
            'old' => [
                'date' => date('Y-m-d'),
                'description' => '',
                'reference' => '',
                'lines' => [
                    ['account_id' => '', 'partner_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
                    ['account_id' => '', 'partner_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
                ],
            ],
        ]);
    }

    public function store(Request $request): void
    {
        $date = $request->input('date');
        $description = trim((string) $request->input('description'));
        $reference = trim((string) $request->input('reference')) ?: null;
        $lines = $this->collectLines($request);

        $errors = [];

        if (!$date) {
            $errors['date'] = 'Датумот е задолжителен.';
        }

        if ($description === '') {
            $errors['description'] = 'Описот е задолжителен.';
        }

        if (!$errors) {
            try {
                $entryId = $this->ledger->postEntry($date, $description, $reference, $lines);
                Response::redirect("/journal/$entryId");
                return;
            } catch (InvalidArgumentException|RuntimeException $e) {
                $errors['lines'] = $e->getMessage();
            }
        }

        Response::view('journal/form', [
            'pageTitle' => 'Нов запис',
            'activeNav' => 'journal',
            'breadcrumb' => ['Почетна' => '/', 'Дневник' => '/journal', 'Нов запис'],
            'accounts' => $this->accounts->all(),
            'partners' => $this->partners->all(),
            'errors' => $errors,
            'old' => [
                'date' => $date,
                'description' => $description,
                'reference' => $reference,
                'lines' => $lines ?: [
                    ['account_id' => '', 'partner_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
                    ['account_id' => '', 'partner_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
                ],
            ],
        ]);
    }

    private function collectLines(Request $request): array
    {
        $accountIds = $request->input('line_account_id', []);
        $partnerIds = $request->input('line_partner_id', []);
        $debits = $request->input('line_debit', []);
        $credits = $request->input('line_credit', []);
        $descriptions = $request->input('line_description', []);

        $lines = [];
        foreach ($accountIds as $i => $accountId) {
            if ($accountId === '' && ($debits[$i] ?? '') === '' && ($credits[$i] ?? '') === '') {
                continue;
            }

            $lines[] = [
                'account_id' => $accountId,
                'partner_id' => $partnerIds[$i] ?? '',
                'debit' => $debits[$i] ?? '',
                'credit' => $credits[$i] ?? '',
                'description' => $descriptions[$i] ?? '',
            ];
        }

        return $lines;
    }

    /** @return array<int, \App\Domain\Accounting\Account> */
    private function accountsById(): array
    {
        $accounts = $this->accounts->all();

        return array_combine(array_map(fn ($a) => $a->id, $accounts), $accounts);
    }

    /** @return array<int, \App\Domain\Partners\Partner> */
    private function partnersById(): array
    {
        $partners = $this->partners->all();

        return array_combine(array_map(fn ($p) => $p->id, $partners), $partners);
    }
}
