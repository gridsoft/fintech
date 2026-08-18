<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AccountRepository;
use App\Repository\AdvanceApplicationRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Service\AdvanceService;
use InvalidArgumentException;
use RuntimeException;

class AdvanceController
{
    private AdvanceApplicationRepository $applications;
    private BankTransactionRepository $transactions;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PartnerRepository $partners;
    private AccountRepository $accounts;
    private AdvanceService $service;

    public function __construct()
    {
        $this->applications = new AdvanceApplicationRepository();
        $this->transactions = new BankTransactionRepository();
        $this->invoices = new InvoiceRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
        $this->partners = new PartnerRepository();
        $this->accounts = new AccountRepository();
        $this->service = new AdvanceService($this->applications, $this->transactions, $this->invoices, $this->purchaseInvoices, $this->accounts);
    }

    public function index(Request $request): void
    {
        $partnersById = $this->partnersById();

        Response::view('advances/index', [
            'pageTitle' => 'Аванси',
            'activeNav' => 'advances',
            'breadcrumb' => ['Почетна' => '/', 'Аванси'],
            'received' => $this->applications->openReceivedAdvances(),
            'given' => $this->applications->openGivenAdvances(),
            'partnersById' => $partnersById,
            'glAccounts' => $this->accounts->postable(),
            'applyData' => $this->applyDataForOpenAdvances(),
        ]);
    }

    public function apply(Request $request, string $id): void
    {
        $bankTransactionId = (int) $id;
        $invoiceType = $request->input('invoice_type');
        $invoiceId = $request->input('invoice_id');
        $amount = (string) $request->input('amount');
        $glAccountId = $request->input('account_id');

        if (!$invoiceType || !$invoiceId || !$amount || !$glAccountId) {
            Response::html('<h1>Грешка</h1><p>Изберете фактура, износ и конто.</p><p><a href="/advances">Назад</a></p>', 422);
            return;
        }

        try {
            if ($invoiceType === 'sales') {
                $this->service->applyToSalesInvoice($bankTransactionId, (int) $invoiceId, $amount, (int) $glAccountId);
            } elseif ($invoiceType === 'purchase') {
                $this->service->applyToPurchaseInvoice($bankTransactionId, (int) $invoiceId, $amount, (int) $glAccountId);
            } else {
                throw new InvalidArgumentException('Непознат тип фактура.');
            }
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/advances">Назад</a></p>', 422);
            return;
        }

        Response::redirect('/advances');
    }

    /**
     * За секој отворен аванс (примен или даден) со таговиран партнер, ги
     * подготвува партнеровите отворени фактури (филтрирани по насока,
     * истиот принцип како bank-statements легacy modal) + преостанато
     * салдо, за "Примени на фактура" модалот.
     *
     * @return array<int, array{openInvoices: array, outstandingByInvoiceId: array<string, string>, invoiceType: string}>
     */
    private function applyDataForOpenAdvances(): array
    {
        $data = [];

        foreach ($this->applications->openReceivedAdvances() as $row) {
            $transaction = $row['transaction'];

            if (!$transaction->partnerId || isset($data[$transaction->id])) {
                continue;
            }

            $openInvoices = $this->invoices->openForMatchingByPartner($transaction->partnerId);
            $outstandingByInvoiceId = [];

            foreach ($openInvoices as $invoice) {
                $outstandingByInvoiceId[$invoice->id] = $this->service->outstandingForSalesInvoice($invoice->id, $invoice->grossInBaseCurrency());
            }

            $data[$transaction->id] = ['invoiceType' => 'sales', 'openInvoices' => $openInvoices, 'outstandingByInvoiceId' => $outstandingByInvoiceId];
        }

        foreach ($this->applications->openGivenAdvances() as $row) {
            $transaction = $row['transaction'];

            if (!$transaction->partnerId || isset($data[$transaction->id])) {
                continue;
            }

            $openInvoices = $this->purchaseInvoices->openForMatchingByPartner($transaction->partnerId);
            $outstandingByInvoiceId = [];

            foreach ($openInvoices as $invoice) {
                $outstandingByInvoiceId[$invoice->id] = $this->service->outstandingForPurchaseInvoice($invoice->id, $invoice->grossInBaseCurrency());
            }

            $data[$transaction->id] = ['invoiceType' => 'purchase', 'openInvoices' => $openInvoices, 'outstandingByInvoiceId' => $outstandingByInvoiceId];
        }

        return $data;
    }

    /** @return array<int, \App\Domain\Partners\Partner> */
    private function partnersById(): array
    {
        $partners = $this->partners->all();

        return array_combine(array_map(fn ($p) => $p->id, $partners), $partners);
    }
}
