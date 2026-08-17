<?php

namespace App\Service;

use App\Domain\Accounting\BankStatement;
use App\Domain\Accounting\BankTransaction;
use App\Repository\AccountRepository;
use App\Repository\BankStatementRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\PurchaseInvoiceRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Порамнува банкарска трансакција со ТОЧНО една излезна или влезна
 * фактура. Нема посебно чувано поле за "преостанато салдо" на фактурата —
 * тоа секогаш е totalGross минус збирот на веќе матчирани трансакции
 * (BankTransactionRepository::matchedAmountForInvoice()). Делумно плаќање
 * едноставно значи фактурата останува во тековниот статус со намалено
 * (пресметано) салдо; статусот станува 'paid' само кога салдото стигне 0.
 * Види PLAN.md Фаза 7.
 */
class PaymentMatchingService
{
    private const ACCOUNT_RECEIVABLES_DOMESTIC = '1200';
    private const ACCOUNT_RECEIVABLES_FOREIGN = '1201';
    private const ACCOUNT_PAYABLES_DOMESTIC = '2200';
    private const ACCOUNT_PAYABLES_FOREIGN = '2201';

    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PartnerRepository $partners;
    private AccountRepository $accounts;
    private LedgerService $ledger;

    public function __construct(
        ?BankStatementRepository $statements = null,
        ?BankTransactionRepository $transactions = null,
        ?InvoiceRepository $invoices = null,
        ?PurchaseInvoiceRepository $purchaseInvoices = null,
        ?PartnerRepository $partners = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null
    ) {
        $this->statements = $statements ?? new BankStatementRepository();
        $this->transactions = $transactions ?? new BankTransactionRepository();
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->purchaseInvoices = $purchaseInvoices ?? new PurchaseInvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    public function createStatement(int $accountId, string $date, ?string $reference): int
    {
        return $this->statements->create(new BankStatement($accountId, $date, $reference ?: null));
    }

    public function addTransaction(int $statementId, string $date, ?string $description, string $amount, string $direction, ?int $partnerId): int
    {
        if (!$this->statements->find($statementId)) {
            throw new InvalidArgumentException('Изводот не е пронајден.');
        }

        if (!in_array($direction, BankTransaction::DIRECTIONS, true)) {
            throw new InvalidArgumentException('Изберете важечка насока (влез/излез).');
        }

        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Износот мора да биде поголем од нула.');
        }

        return $this->transactions->create(new BankTransaction(
            $statementId,
            $date,
            number_format((float) $amount, 2, '.', ''),
            $direction,
            $description ?: null,
            $partnerId
        ));
    }

    public function matchToSalesInvoice(int $transactionId, int $invoiceId): void
    {
        $transaction = $this->loadUnmatchedTransaction($transactionId, 'in', 'Уплата');

        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice || $invoice->status !== 'issued') {
            throw new InvalidArgumentException('Фактурата не постои или не е во статус „издадена“.');
        }

        $partner = $this->partners->find($invoice->partnerId);

        if (!$partner) {
            throw new RuntimeException('Партнерот на фактурата не постои.');
        }

        $outstanding = bcsub($invoice->totalGross, $this->transactions->matchedAmountForInvoice('sales', $invoiceId), 2);

        if (bccomp($transaction->amount, $outstanding, 2) > 0) {
            throw new InvalidArgumentException("Износот на трансакцијата ({$transaction->amount}) е поголем од преостанатото салдо на фактурата ($outstanding).");
        }

        $receivablesCode = $partner->isForeign() ? self::ACCOUNT_RECEIVABLES_FOREIGN : self::ACCOUNT_RECEIVABLES_DOMESTIC;
        $receivablesAccount = $this->accounts->findByCode($receivablesCode);

        if (!$receivablesAccount) {
            throw new RuntimeException("Не постои стандардна сметка за побарувања ($receivablesCode) во контниот план.");
        }

        $entryId = $this->ledger->postEntry($transaction->date, "Уплата по фактура {$invoice->number}", $invoice->number, [
            ['account_id' => $transaction->accountId, 'debit' => $transaction->amount, 'credit' => '0', 'description' => "Уплата по фактура {$invoice->number}"],
            ['account_id' => $receivablesAccount->id, 'partner_id' => $invoice->partnerId, 'debit' => '0', 'credit' => $transaction->amount, 'description' => "Уплата по фактура {$invoice->number}"],
        ]);

        $this->transactions->markMatched($transaction->id, 'sales', $invoiceId, $entryId);

        if (bccomp($transaction->amount, $outstanding, 2) === 0) {
            $this->invoices->updateStatus($invoiceId, 'paid');
        }
    }

    public function matchToPurchaseInvoice(int $transactionId, int $invoiceId): void
    {
        $transaction = $this->loadUnmatchedTransaction($transactionId, 'out', 'Исплата');

        $invoice = $this->purchaseInvoices->find($invoiceId);

        if (!$invoice || $invoice->status !== 'posted') {
            throw new InvalidArgumentException('Фактурата не постои или не е во статус „заведена“.');
        }

        $partner = $this->partners->find($invoice->partnerId);

        if (!$partner) {
            throw new RuntimeException('Партнерот на фактурата не постои.');
        }

        $outstanding = bcsub($invoice->totalGross, $this->transactions->matchedAmountForInvoice('purchase', $invoiceId), 2);

        if (bccomp($transaction->amount, $outstanding, 2) > 0) {
            throw new InvalidArgumentException("Износот на трансакцијата ({$transaction->amount}) е поголем од преостанатото салдо на фактурата ($outstanding).");
        }

        $payablesCode = $partner->isForeign() ? self::ACCOUNT_PAYABLES_FOREIGN : self::ACCOUNT_PAYABLES_DOMESTIC;
        $payablesAccount = $this->accounts->findByCode($payablesCode);

        if (!$payablesAccount) {
            throw new RuntimeException("Не постои стандардна сметка за обврски кон добавувачи ($payablesCode) во контниот план.");
        }

        $entryId = $this->ledger->postEntry($transaction->date, "Исплата по влезна фактура {$invoice->supplierNumber}", $invoice->supplierNumber, [
            ['account_id' => $payablesAccount->id, 'partner_id' => $invoice->partnerId, 'debit' => $transaction->amount, 'credit' => '0', 'description' => "Исплата по влезна фактура {$invoice->supplierNumber}"],
            ['account_id' => $transaction->accountId, 'debit' => '0', 'credit' => $transaction->amount, 'description' => "Исплата по влезна фактура {$invoice->supplierNumber}"],
        ]);

        $this->transactions->markMatched($transaction->id, 'purchase', $invoiceId, $entryId);

        if (bccomp($transaction->amount, $outstanding, 2) === 0) {
            $this->purchaseInvoices->updateStatus($invoiceId, 'paid');
        }
    }

    private function loadUnmatchedTransaction(int $transactionId, string $expectedDirection, string $expectedDirectionLabel): BankTransaction
    {
        $transaction = $this->transactions->find($transactionId);

        if (!$transaction) {
            throw new InvalidArgumentException('Трансакцијата не е пронајдена.');
        }

        if ($transaction->matchedStatus !== 'unmatched') {
            throw new RuntimeException('Трансакцијата веќе е матчирана.');
        }

        if ($transaction->direction !== $expectedDirection) {
            throw new InvalidArgumentException("Само трансакции од тип „{$expectedDirectionLabel}“ можат да се матчираат со овој тип фактура.");
        }

        return $transaction;
    }
}
