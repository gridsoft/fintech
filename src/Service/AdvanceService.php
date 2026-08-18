<?php

namespace App\Service;

use App\Domain\Accounting\AdvanceApplication;
use App\Repository\AccountRepository;
use App\Repository\AdvanceApplicationRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PurchaseInvoiceRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Примена на аванс (веќе книжена банкарска трансакција на конто 2230/2231
 * или 3700/3701 — Фаза 7 "Додади трансакција без фактура") на фактура.
 * Комбинираното преостанато на фактура (гросс минус матчирани банкарски
 * трансакции МИНУС веќе применети аванси) е пресметано овде истоветно со
 * PaymentMatchingService, за да не се дозволи двојно "плаќање" на иста
 * фактура преку двата пата.
 */
class AdvanceService
{
    private AdvanceApplicationRepository $applications;
    private BankTransactionRepository $transactions;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private AccountRepository $accounts;
    private LedgerService $ledger;

    public function __construct(
        ?AdvanceApplicationRepository $applications = null,
        ?BankTransactionRepository $transactions = null,
        ?InvoiceRepository $invoices = null,
        ?PurchaseInvoiceRepository $purchaseInvoices = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null
    ) {
        $this->applications = $applications ?? new AdvanceApplicationRepository();
        $this->transactions = $transactions ?? new BankTransactionRepository();
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->purchaseInvoices = $purchaseInvoices ?? new PurchaseInvoiceRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    public function applyToSalesInvoice(int $bankTransactionId, int $invoiceId, string $amount, int $glAccountId): void
    {
        $transaction = $this->loadAdvanceTransaction($bankTransactionId, AdvanceApplicationRepository::RECEIVED_ADVANCE_CODES);

        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice || $invoice->status !== 'issued') {
            throw new InvalidArgumentException('Фактурата не постои или не е во статус „издадена“.');
        }

        if (!$this->accounts->find($glAccountId)) {
            throw new InvalidArgumentException('Изберете важечко конто.');
        }

        $amount = number_format((float) $amount, 2, '.', '');
        $this->assertWithinAdvanceRemaining($transaction->id, $transaction->amount, $amount);

        $outstanding = $this->outstandingForSalesInvoice($invoiceId, $invoice->grossInBaseCurrency());

        if (bccomp($amount, $outstanding, 2) > 0) {
            throw new InvalidArgumentException("Износот ($amount) е поголем од преостанатото салдо на фактурата ($outstanding).");
        }

        $entryId = $this->ledger->postEntry($transaction->date, "Примена на аванс на фактура {$invoice->number}", $invoice->number, [
            ['account_id' => $transaction->glAccountId, 'partner_id' => $transaction->partnerId, 'debit' => $amount, 'credit' => '0', 'description' => "Примена на аванс на фактура {$invoice->number}"],
            ['account_id' => $glAccountId, 'partner_id' => $invoice->partnerId, 'debit' => '0', 'credit' => $amount, 'description' => "Примена на аванс на фактура {$invoice->number}"],
        ]);

        $this->applications->create(new AdvanceApplication($transaction->id, 'sales', $invoiceId, $amount, $entryId, $transaction->date));

        if (bccomp($amount, $outstanding, 2) === 0) {
            $this->invoices->updateStatus($invoiceId, 'paid');
        }
    }

    public function applyToPurchaseInvoice(int $bankTransactionId, int $invoiceId, string $amount, int $glAccountId): void
    {
        $transaction = $this->loadAdvanceTransaction($bankTransactionId, AdvanceApplicationRepository::GIVEN_ADVANCE_CODES);

        $invoice = $this->purchaseInvoices->find($invoiceId);

        if (!$invoice || $invoice->status !== 'posted') {
            throw new InvalidArgumentException('Фактурата не постои или не е во статус „заведена“.');
        }

        if (!$this->accounts->find($glAccountId)) {
            throw new InvalidArgumentException('Изберете важечко конто.');
        }

        $amount = number_format((float) $amount, 2, '.', '');
        $this->assertWithinAdvanceRemaining($transaction->id, $transaction->amount, $amount);

        $outstanding = $this->outstandingForPurchaseInvoice($invoiceId, $invoice->grossInBaseCurrency());

        if (bccomp($amount, $outstanding, 2) > 0) {
            throw new InvalidArgumentException("Износот ($amount) е поголем од преостанатото салдо на фактурата ($outstanding).");
        }

        $entryId = $this->ledger->postEntry($transaction->date, "Примена на аванс на влезна фактура {$invoice->supplierNumber}", $invoice->supplierNumber, [
            ['account_id' => $glAccountId, 'partner_id' => $invoice->partnerId, 'debit' => $amount, 'credit' => '0', 'description' => "Примена на аванс на влезна фактура {$invoice->supplierNumber}"],
            ['account_id' => $transaction->glAccountId, 'partner_id' => $transaction->partnerId, 'debit' => '0', 'credit' => $amount, 'description' => "Примена на аванс на влезна фактура {$invoice->supplierNumber}"],
        ]);

        $this->applications->create(new AdvanceApplication($transaction->id, 'purchase', $invoiceId, $amount, $entryId, $transaction->date));

        if (bccomp($amount, $outstanding, 2) === 0) {
            $this->purchaseInvoices->updateStatus($invoiceId, 'paid');
        }
    }

    /** Комбинирано преостанато на излезна фактура — гросс минус матчирани банкарски трансакции минус применети аванси. */
    public function outstandingForSalesInvoice(int $invoiceId, string $totalGross): string
    {
        $matched = $this->transactions->matchedAmountForInvoice('sales', $invoiceId);
        $applied = $this->applications->appliedAmountForInvoice('sales', $invoiceId);

        return bcsub(bcsub($totalGross, $matched, 2), $applied, 2);
    }

    /** Комбинирано преостанато на влезна фактура — истиот принцип. */
    public function outstandingForPurchaseInvoice(int $invoiceId, string $totalGross): string
    {
        $matched = $this->transactions->matchedAmountForInvoice('purchase', $invoiceId);
        $applied = $this->applications->appliedAmountForInvoice('purchase', $invoiceId);

        return bcsub(bcsub($totalGross, $matched, 2), $applied, 2);
    }

    private function assertWithinAdvanceRemaining(int $bankTransactionId, string $transactionAmount, string $amount): void
    {
        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('Износот мора да биде поголем од нула.');
        }

        $remaining = bcsub($transactionAmount, $this->applications->appliedAmountForTransaction($bankTransactionId), 2);

        if (bccomp($amount, $remaining, 2) > 0) {
            throw new InvalidArgumentException("Износот ($amount) е поголем од преостанатото на авансот ($remaining).");
        }
    }

    /** @param string[] $allowedAccountCodes */
    private function loadAdvanceTransaction(int $bankTransactionId, array $allowedAccountCodes)
    {
        $transaction = $this->transactions->find($bankTransactionId);

        if (!$transaction) {
            throw new InvalidArgumentException('Трансакцијата не е пронајдена.');
        }

        if ($transaction->matchedStatus !== 'matched' || !$transaction->glAccountId) {
            throw new RuntimeException('Трансакцијата не е книжена аванс-трансакција.');
        }

        $account = $this->accounts->find($transaction->glAccountId);

        if (!$account || !in_array($account->code, $allowedAccountCodes, true)) {
            throw new InvalidArgumentException('Трансакцијата не е книжена на конто за аванси.');
        }

        if (!$transaction->partnerId) {
            throw new InvalidArgumentException('Авансот нема таговиран партнер.');
        }

        return $transaction;
    }
}
