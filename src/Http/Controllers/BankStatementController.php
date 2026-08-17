<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AccountRepository;
use App\Repository\BankStatementRepository;
use App\Repository\BankTransactionRepository;
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
    private PaymentMatchingService $service;

    public function __construct()
    {
        $this->statements = new BankStatementRepository();
        $this->transactions = new BankTransactionRepository();
        $this->accounts = new AccountRepository();
        $this->partners = new PartnerRepository();
        $this->invoices = new InvoiceRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
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
            'old' => ['account_id' => '', 'date' => date('Y-m-d'), 'reference' => ''],
        ]);
    }

    public function store(Request $request): void
    {
        $accountId = $request->input('account_id');
        $date = $request->input('date');
        $reference = trim((string) $request->input('reference'));

        $errors = [];

        if (!$accountId) {
            $errors['account_id'] = 'Изберете парична сметка.';
        }

        if (!$date) {
            $errors['date'] = 'Датумот е задолжителен.';
        }

        if (!$errors) {
            $statementId = $this->service->createStatement((int) $accountId, $date, $reference);
            Response::redirect("/bank-statements/$statementId");
            return;
        }

        Response::view('bank-statements/form', [
            'pageTitle' => 'Нов извод',
            'activeNav' => 'bank-statements',
            'breadcrumb' => ['Почетна' => '/', 'Изводи' => '/bank-statements', 'Нов извод'],
            'cashAccounts' => $this->accounts->cashAccounts(),
            'errors' => $errors,
            'old' => ['account_id' => $accountId, 'date' => $date, 'reference' => $reference],
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $statement = $this->statements->find((int) $id);

        if (!$statement) {
            Response::html('<h1>404</h1><p>Изводот не е пронајден.</p>', 404);
            return;
        }

        Response::view('bank-statements/show', [
            'pageTitle' => 'Извод',
            'activeNav' => 'bank-statements',
            'breadcrumb' => ['Почетна' => '/', 'Изводи' => '/bank-statements', 'Преглед'],
            'statement' => $statement,
            'account' => $this->accounts->find($statement->accountId),
            'partners' => $this->partners->all(),
            'partnersById' => $this->partnersById(),
            'matchData' => $this->matchDataForUnmatchedTransactions($statement->transactions),
            'errors' => [],
        ]);
    }

    public function addTransaction(Request $request, string $id): void
    {
        $statementId = (int) $id;
        $partnerId = $request->input('partner_id');

        try {
            $this->service->addTransaction(
                $statementId,
                $request->input('transaction_date'),
                $request->input('description'),
                (string) $request->input('amount'),
                $request->input('direction'),
                $partnerId !== '' ? (int) $partnerId : null
            );
        } catch (InvalidArgumentException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/bank-statements/' . $statementId . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/bank-statements/$statementId");
    }

    public function match(Request $request, string $id): void
    {
        $transactionId = (int) $id;
        $invoiceType = $request->input('invoice_type');
        $invoiceId = $request->input('invoice_id');
        $transaction = $this->transactions->find($transactionId);

        if (!$transaction) {
            Response::html('<h1>404</h1><p>Трансакцијата не е пронајдена.</p>', 404);
            return;
        }

        if (!$invoiceType || !$invoiceId) {
            Response::html('<h1>Грешка</h1><p>Изберете фактура.</p><p><a href="/bank-statements/' . $transaction->bankStatementId . '">Назад</a></p>', 422);
            return;
        }

        try {
            if ($invoiceType === 'sales') {
                $this->service->matchToSalesInvoice($transactionId, (int) $invoiceId);
            } elseif ($invoiceType === 'purchase') {
                $this->service->matchToPurchaseInvoice($transactionId, (int) $invoiceId);
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
     * модалот за поврзување на прегледот на изводот (истиот view, без
     * навигација кон посебна страница).
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
                $outstandingByInvoiceId['sales_' . $invoice->id] = bcsub($invoice->totalGross, $this->transactions->matchedAmountForInvoice('sales', $invoice->id), 2);
            }
            foreach ($openPurchaseInvoices as $invoice) {
                $outstandingByInvoiceId['purchase_' . $invoice->id] = bcsub($invoice->totalGross, $this->transactions->matchedAmountForInvoice('purchase', $invoice->id), 2);
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
