<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AccountRepository;
use App\Repository\BankStatementRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\CurrencyRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Service\PaymentMatchingService;
use InvalidArgumentException;
use RuntimeException;

class BankStatementController
{
    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private AccountRepository $accounts;
    private PartnerRepository $partners;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private CurrencyRepository $currencies;
    private PaymentMatchingService $service;

    public function __construct()
    {
        $this->statements = new BankStatementRepository();
        $this->transactions = new BankTransactionRepository();
        $this->accounts = new AccountRepository();
        $this->partners = new PartnerRepository();
        $this->invoices = new InvoiceRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
        $this->currencies = new CurrencyRepository();
        $this->service = new PaymentMatchingService($this->statements, $this->transactions);
    }

    public function index(Request $request): void
    {
        Response::view('bank-statements/index', [
            'pageTitle' => 'Изводи',
            'activeNav' => 'bank-statements',
            'breadcrumb' => ['Почетна' => '/', 'Изводи'],
            'statements' => $this->statements->all(),
            'accountsById' => $this->accountsById(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('bank-statements/form', [
            'pageTitle' => 'Нов извод',
            'activeNav' => 'bank-statements',
            'breadcrumb' => ['Почетна' => '/', 'Изводи' => '/bank-statements', 'Нов извод'],
            'cashAccounts' => $this->accounts->cashAccounts(),
            'errors' => [],
            'old' => ['account_id' => '', 'date' => date('Y-m-d'), 'reference' => '', 'opening_balance' => '0.00'],
        ]);
    }

    public function store(Request $request): void
    {
        $accountId = $request->input('account_id');
        $date = $request->input('date');
        $reference = trim((string) $request->input('reference'));
        $openingBalance = $request->input('opening_balance', '0.00');

        $errors = [];

        if (!$accountId) {
            $errors['account_id'] = 'Изберете парична сметка.';
        }

        if (!$date) {
            $errors['date'] = 'Датумот е задолжителен.';
        }

        if (!$errors) {
            $statementId = $this->service->createStatement((int) $accountId, $date, $reference, (string) $openingBalance);
            Response::redirect("/bank-statements/$statementId");
            return;
        }

        Response::view('bank-statements/form', [
            'pageTitle' => 'Нов извод',
            'activeNav' => 'bank-statements',
            'breadcrumb' => ['Почетна' => '/', 'Изводи' => '/bank-statements', 'Нов извод'],
            'cashAccounts' => $this->accounts->cashAccounts(),
            'errors' => $errors,
            'old' => ['account_id' => $accountId, 'date' => $date, 'reference' => $reference, 'opening_balance' => $openingBalance],
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $statement = $this->statements->find((int) $id);

        if (!$statement) {
            Response::html('<h1>404</h1><p>Изводот не е пронајден.</p>', 404);
            return;
        }

        $glAccounts = array_values(array_filter(
            $this->accounts->postable(),
            fn ($account) => $account->id !== $statement->accountId
        ));

        Response::view('bank-statements/show', [
            'pageTitle' => 'Извод',
            'activeNav' => 'bank-statements',
            'breadcrumb' => ['Почетна' => '/', 'Изводи' => '/bank-statements', 'Преглед'],
            'statement' => $statement,
            'account' => $this->accounts->find($statement->accountId),
            'partners' => $this->partners->all(),
            'partnersById' => $this->partnersById(),
            'accountsById' => $this->accountsById(),
            'glAccounts' => $glAccounts,
            'baseCurrencyId' => $this->currencies->base()->id,
            'openSalesInvoicesByPartner' => $this->service->openSalesInvoicesByPartner(),
            'openPurchaseInvoicesByPartner' => $this->service->openPurchaseInvoicesByPartner(),
            'matchData' => $this->matchDataForUnmatchedTransactions($statement->transactions),
            'errors' => [],
        ]);
    }

    /**
     * Внесува повеќе трансакции наеднаш, во еден ред секоја — со или без
     * фактура (не се одвоени форми/табели). Ако редот има избрана фактура
     * (и партнер), се книжи преку addMatchedTransaction(); ако нема
     * фактура, се книжи директно наспроти избраното конто (addTransaction()
     * + postManual()). Редовите се обработуваат секвенцијално; ако некој
     * ред падне, претходните веќе се книжени (свесен trade-off, секој ред
     * е внатрешно конзистентен, види PLAN).
     */
    public function addTransactions(Request $request, string $id): void
    {
        $statementId = (int) $id;
        $statement = $this->statements->find($statementId);

        if (!$statement) {
            Response::html('<h1>404</h1><p>Изводот не е пронајден.</p>', 404);
            return;
        }

        $partnerIds = $request->input('partner_id', []);
        $glAccountIds = $request->input('gl_account_id', []);
        $invoicePicks = $request->input('invoice_pick', []);
        $amountsIn = $request->input('amount_in', []);
        $amountsOut = $request->input('amount_out', []);
        $descriptions = $request->input('description', []);
        $fxCloses = $request->input('fx_close', []);

        foreach ($glAccountIds as $i => $glAccountId) {
            $partnerId = $partnerIds[$i] ?? '';
            $invoicePick = (string) ($invoicePicks[$i] ?? '');
            $amountIn = (string) ($amountsIn[$i] ?? '');
            $amountOut = (string) ($amountsOut[$i] ?? '');

            if ($glAccountId === '' && $partnerId === '' && $invoicePick === '' && $amountIn === '' && $amountOut === '') {
                continue;
            }

            $hasIn = $amountIn !== '' && (float) $amountIn > 0;
            $hasOut = $amountOut !== '' && (float) $amountOut > 0;

            if ($hasIn === $hasOut) {
                Response::html('<h1>Грешка</h1><p>Ред ' . ((int) $i + 1) . ': пополнете точно едно од Прилив/Одлив.</p><p><a href="/bank-statements/' . $statementId . '">Назад</a></p>', 422);
                return;
            }

            if (!$glAccountId) {
                Response::html('<h1>Грешка</h1><p>Ред ' . ((int) $i + 1) . ': изберете конто.</p><p><a href="/bank-statements/' . $statementId . '">Назад</a></p>', 422);
                return;
            }

            $amount = $hasIn ? $amountIn : $amountOut;
            $direction = $hasIn ? 'in' : 'out';

            try {
                if ($invoicePick !== '') {
                    $parts = explode(':', $invoicePick, 2);

                    if (count($parts) !== 2 || !in_array($parts[0], ['sales', 'purchase'], true)) {
                        Response::html('<h1>Грешка</h1><p>Ред ' . ((int) $i + 1) . ': изберете важечка фактура.</p><p><a href="/bank-statements/' . $statementId . '">Назад</a></p>', 422);
                        return;
                    }

                    if (!$partnerId) {
                        Response::html('<h1>Грешка</h1><p>Ред ' . ((int) $i + 1) . ': изберете партнер.</p><p><a href="/bank-statements/' . $statementId . '">Назад</a></p>', 422);
                        return;
                    }

                    $this->service->addMatchedTransaction(
                        $statementId,
                        $statement->date,
                        $descriptions[$i] ?? null,
                        null,
                        $amount,
                        $direction,
                        (int) $partnerId,
                        (int) $glAccountId,
                        $parts[0],
                        (int) $parts[1],
                        !empty($fxCloses[$i])
                    );
                } else {
                    $transactionId = $this->service->addTransaction(
                        $statementId,
                        $statement->date,
                        $descriptions[$i] ?? null,
                        null,
                        $amount,
                        $direction,
                        $partnerId !== '' ? (int) $partnerId : null,
                        (int) $glAccountId
                    );
                    $this->service->postManual($transactionId);
                }
            } catch (InvalidArgumentException|RuntimeException $e) {
                Response::html('<h1>Грешка</h1><p>Ред ' . ((int) $i + 1) . ': ' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/bank-statements/' . $statementId . '">Назад</a></p>', 422);
                return;
            }
        }

        Response::redirect("/bank-statements/$statementId");
    }

    public function match(Request $request, string $id): void
    {
        $transactionId = (int) $id;
        $invoiceType = $request->input('invoice_type');
        $invoiceId = $request->input('invoice_id');
        $glAccountId = $request->input('account_id');
        $transaction = $this->transactions->find($transactionId);

        if (!$transaction) {
            Response::html('<h1>404</h1><p>Трансакцијата не е пронајдена.</p>', 404);
            return;
        }

        if (!$invoiceType || !$invoiceId || !$glAccountId) {
            Response::html('<h1>Грешка</h1><p>Изберете фактура и конто.</p><p><a href="/bank-statements/' . $transaction->bankStatementId . '">Назад</a></p>', 422);
            return;
        }

        $closeWithFxDifference = $request->input('close_with_fx_difference') === '1';

        try {
            if ($invoiceType === 'sales') {
                $this->service->matchToSalesInvoice($transactionId, (int) $invoiceId, (int) $glAccountId, $closeWithFxDifference);
            } elseif ($invoiceType === 'purchase') {
                $this->service->matchToPurchaseInvoice($transactionId, (int) $invoiceId, (int) $glAccountId, $closeWithFxDifference);
            } else {
                throw new InvalidArgumentException('Непознат тип фактура.');
            }
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/bank-statements/' . $transaction->bankStatementId . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect('/bank-statements/' . $transaction->bankStatementId);
    }

    /**
     * За секоја неповрзана трансакција со избран партнер, ги подготвува
     * отворените фактури (само од тој партнер) + преостанато салдо, за
     * legacy модалот за поврзување (релевантно само за стари неповрзани
     * редови — нови трансакции отсека веднаш се книжат).
     *
     * @param \App\Domain\Accounting\BankTransaction[] $transactions
     * @return array<int, array{openSalesInvoices: array, openPurchaseInvoices: array, outstandingByInvoiceId: array<string, string>}>
     */
    private function matchDataForUnmatchedTransactions(array $transactions): array
    {
        $data = [];

        foreach ($transactions as $transaction) {
            if ($transaction->matchedStatus !== 'unmatched' || !$transaction->partnerId) {
                continue;
            }

            $openSalesInvoices = $transaction->direction === 'in' ? $this->invoices->openForMatchingByPartner($transaction->partnerId) : [];
            $openPurchaseInvoices = $transaction->direction === 'out' ? $this->purchaseInvoices->openForMatchingByPartner($transaction->partnerId) : [];

            $outstandingByInvoiceId = [];
            foreach ($openSalesInvoices as $invoice) {
                $outstandingByInvoiceId['sales_' . $invoice->id] = $this->service->outstandingForSales($invoice->grossInBaseCurrency(), $invoice->id);
            }
            foreach ($openPurchaseInvoices as $invoice) {
                $outstandingByInvoiceId['purchase_' . $invoice->id] = $this->service->outstandingForPurchase($invoice->grossInBaseCurrency(), $invoice->id);
            }

            $data[$transaction->id] = [
                'openSalesInvoices' => $openSalesInvoices,
                'openPurchaseInvoices' => $openPurchaseInvoices,
                'outstandingByInvoiceId' => $outstandingByInvoiceId,
            ];
        }

        return $data;
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
