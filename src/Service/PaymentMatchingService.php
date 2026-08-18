<?php

namespace App\Service;

use App\Domain\Accounting\BankStatement;
use App\Domain\Accounting\BankTransaction;
use App\Repository\AccountRepository;
use App\Repository\AdvanceApplicationRepository;
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
 *
 * Конто (gl_account_id) е СЕКОГАШ рачен избор на корисникот — нема
 * автоматско разрешување според partner->isForeign(). Секоја трансакција,
 * со или без поврзана фактура, веднаш се книжи во главната книга наспроти
 * избраното конто.
 *
 * Види PLAN.md Фаза 7.
 */
class PaymentMatchingService
{
    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PartnerRepository $partners;
    private AccountRepository $accounts;
    private LedgerService $ledger;
    private AdvanceApplicationRepository $advanceApplications;

    public function __construct(
        ?BankStatementRepository $statements = null,
        ?BankTransactionRepository $transactions = null,
        ?InvoiceRepository $invoices = null,
        ?PurchaseInvoiceRepository $purchaseInvoices = null,
        ?PartnerRepository $partners = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null,
        ?AdvanceApplicationRepository $advanceApplications = null
    ) {
        $this->statements = $statements ?? new BankStatementRepository();
        $this->transactions = $transactions ?? new BankTransactionRepository();
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->purchaseInvoices = $purchaseInvoices ?? new PurchaseInvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->ledger = $ledger ?? new LedgerService();
        $this->advanceApplications = $advanceApplications ?? new AdvanceApplicationRepository();
    }

    /** Комбинирано преостанато — гросс минус матчирани банкарски трансакции минус применети аванси (AdvanceService). Јавно бидејќи и BankStatementController::matchDataForUnmatchedTransactions() (legacy modal) го користи истото пресметување. */
    public function outstandingForSales(string $totalGross, int $invoiceId): string
    {
        $matched = $this->transactions->matchedAmountForInvoice('sales', $invoiceId);
        $applied = $this->advanceApplications->appliedAmountForInvoice('sales', $invoiceId);

        return bcsub(bcsub($totalGross, $matched, 2), $applied, 2);
    }

    /** @see outstandingForSales */
    public function outstandingForPurchase(string $totalGross, int $invoiceId): string
    {
        $matched = $this->transactions->matchedAmountForInvoice('purchase', $invoiceId);
        $applied = $this->advanceApplications->appliedAmountForInvoice('purchase', $invoiceId);

        return bcsub(bcsub($totalGross, $matched, 2), $applied, 2);
    }

    public function createStatement(int $accountId, string $date, ?string $reference, string $openingBalance = '0.00'): int
    {
        return $this->statements->create(new BankStatement($accountId, $date, $reference ?: null, number_format((float) $openingBalance, 2, '.', '')));
    }

    /**
     * Инсертира ред во изводот (сеуште неповрзан/некнижен) — конто и
     * останатите податоци се веќе познати и се чуваат на редот. За
     * трансакции БЕЗ фактура (Пат А), контролерот веднаш повикува
     * postManual() по ова за да ја книжи; legacy modal-от исто така работи
     * над ред создаден овде, пред да го матчира со фактура.
     */
    public function addTransaction(int $statementId, string $date, ?string $description, ?string $code, string $amount, string $direction, ?int $partnerId, int $glAccountId): int
    {
        if (!$this->statements->find($statementId)) {
            throw new InvalidArgumentException('Изводот не е пронајден.');
        }

        if (!$this->accounts->find($glAccountId)) {
            throw new InvalidArgumentException('Изберете важечко конто.');
        }

        return $this->insertTransactionRow($statementId, $date, $description, $code, $amount, $direction, $partnerId, $glAccountId);
    }

    /**
     * "Пат А" — ја книжи веќе-инсертираната трансакција (без фактура)
     * наспроти конто-то зачувано на редот (пр. банкарски провизии, плати).
     */
    public function postManual(int $transactionId): void
    {
        $transaction = $this->transactions->find($transactionId);

        if (!$transaction) {
            throw new InvalidArgumentException('Трансакцијата не е пронајдена.');
        }

        if ($transaction->matchedStatus !== 'unmatched') {
            throw new RuntimeException('Трансакцијата веќе е матчирана.');
        }

        if (!$transaction->glAccountId) {
            throw new InvalidArgumentException('Трансакцијата нема избрано конто.');
        }

        $label = $transaction->direction === 'in' ? 'Уплата' : 'Исплата';
        $entryLabel = trim($label . ($transaction->description ? " — {$transaction->description}" : ''));

        $entryId = $transaction->direction === 'in'
            ? $this->ledger->postEntry($transaction->date, $entryLabel, $transaction->code, [
                ['account_id' => $transaction->accountId, 'debit' => $transaction->amount, 'credit' => '0', 'description' => $entryLabel],
                ['account_id' => $transaction->glAccountId, 'partner_id' => $transaction->partnerId, 'debit' => '0', 'credit' => $transaction->amount, 'description' => $entryLabel],
            ])
            : $this->ledger->postEntry($transaction->date, $entryLabel, $transaction->code, [
                ['account_id' => $transaction->glAccountId, 'partner_id' => $transaction->partnerId, 'debit' => $transaction->amount, 'credit' => '0', 'description' => $entryLabel],
                ['account_id' => $transaction->accountId, 'debit' => '0', 'credit' => $transaction->amount, 'description' => $entryLabel],
            ]);

        $this->transactions->markMatched($transactionId, null, null, $entryId, $transaction->glAccountId);
    }

    /**
     * "Пат Б" — партнер и фактура се веќе избрани (единствен ред од
     * грид-от). Ја инсертира трансакцијата и веднаш ја матчира.
     */
    public function addMatchedTransaction(int $statementId, string $date, ?string $description, ?string $code, string $amount, string $direction, int $partnerId, int $glAccountId, string $invoiceType, int $invoiceId): int
    {
        if (!$this->statements->find($statementId)) {
            throw new InvalidArgumentException('Изводот не е пронајден.');
        }

        if (!in_array($invoiceType, ['sales', 'purchase'], true)) {
            throw new InvalidArgumentException('Непознат тип фактура.');
        }

        $transactionId = $this->insertTransactionRow($statementId, $date, $description, $code, $amount, $direction, $partnerId, $glAccountId);

        if ($invoiceType === 'sales') {
            $this->matchToSalesInvoice($transactionId, $invoiceId, $glAccountId);
        } else {
            $this->matchToPurchaseInvoice($transactionId, $invoiceId, $glAccountId);
        }

        return $transactionId;
    }

    public function matchToSalesInvoice(int $transactionId, int $invoiceId, int $glAccountId): void
    {
        $transaction = $this->loadUnmatchedTransaction($transactionId, 'in', 'Уплата');

        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice || $invoice->status !== 'issued') {
            throw new InvalidArgumentException('Фактурата не постои или не е во статус „издадена“.');
        }

        if (!$this->accounts->find($glAccountId)) {
            throw new InvalidArgumentException('Изберете важечко конто.');
        }

        $outstanding = $this->outstandingForSales($invoice->totalGross, $invoiceId);

        if (bccomp($transaction->amount, $outstanding, 2) > 0) {
            throw new InvalidArgumentException("Износот на трансакцијата ({$transaction->amount}) е поголем од преостанатото салдо на фактурата ($outstanding).");
        }

        $entryId = $this->ledger->postEntry($transaction->date, "Уплата по фактура {$invoice->number}", $invoice->number, [
            ['account_id' => $transaction->accountId, 'debit' => $transaction->amount, 'credit' => '0', 'description' => "Уплата по фактура {$invoice->number}"],
            ['account_id' => $glAccountId, 'partner_id' => $invoice->partnerId, 'debit' => '0', 'credit' => $transaction->amount, 'description' => "Уплата по фактура {$invoice->number}"],
        ]);

        $this->transactions->markMatched($transaction->id, 'sales', $invoiceId, $entryId, $glAccountId);

        if (bccomp($transaction->amount, $outstanding, 2) === 0) {
            $this->invoices->updateStatus($invoiceId, 'paid');
        }
    }

    public function matchToPurchaseInvoice(int $transactionId, int $invoiceId, int $glAccountId): void
    {
        $transaction = $this->loadUnmatchedTransaction($transactionId, 'out', 'Исплата');

        $invoice = $this->purchaseInvoices->find($invoiceId);

        if (!$invoice || $invoice->status !== 'posted') {
            throw new InvalidArgumentException('Фактурата не постои или не е во статус „заведена“.');
        }

        if (!$this->accounts->find($glAccountId)) {
            throw new InvalidArgumentException('Изберете важечко конто.');
        }

        $outstanding = $this->outstandingForPurchase($invoice->totalGross, $invoiceId);

        if (bccomp($transaction->amount, $outstanding, 2) > 0) {
            throw new InvalidArgumentException("Износот на трансакцијата ({$transaction->amount}) е поголем од преостанатото салдо на фактурата ($outstanding).");
        }

        $entryId = $this->ledger->postEntry($transaction->date, "Исплата по влезна фактура {$invoice->supplierNumber}", $invoice->supplierNumber, [
            ['account_id' => $glAccountId, 'partner_id' => $invoice->partnerId, 'debit' => $transaction->amount, 'credit' => '0', 'description' => "Исплата по влезна фактура {$invoice->supplierNumber}"],
            ['account_id' => $transaction->accountId, 'debit' => '0', 'credit' => $transaction->amount, 'description' => "Исплата по влезна фактура {$invoice->supplierNumber}"],
        ]);

        $this->transactions->markMatched($transaction->id, 'purchase', $invoiceId, $entryId, $glAccountId);

        if (bccomp($transaction->amount, $outstanding, 2) === 0) {
            $this->purchaseInvoices->updateStatus($invoiceId, 'paid');
        }
    }

    /**
     * За сите партнери, ги подготвува отворените излезни/влезни фактури +
     * преостанато салдо — за авто-пополнување на грид-от за внес по ред
     * (Пат Б), без потреба од AJAX (се вградува во страницата).
     *
     * @return array<int, array<int, array{id: int, number: string, date: string, totalGross: string, outstanding: string}>>
     */
    public function openSalesInvoicesByPartner(): array
    {
        $byPartner = [];

        foreach ($this->partners->all() as $partner) {
            $invoices = $this->invoices->openForMatchingByPartner($partner->id);

            if (!$invoices) {
                continue;
            }

            $byPartner[$partner->id] = array_map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date,
                    'totalGross' => $invoice->totalGross,
                    'outstanding' => $this->outstandingForSales($invoice->totalGross, $invoice->id),
                ];
            }, $invoices);
        }

        return $byPartner;
    }

    /** @return array<int, array<int, array{id: int, number: string, date: string, totalGross: string, outstanding: string}>> */
    public function openPurchaseInvoicesByPartner(): array
    {
        $byPartner = [];

        foreach ($this->partners->all() as $partner) {
            $invoices = $this->purchaseInvoices->openForMatchingByPartner($partner->id);

            if (!$invoices) {
                continue;
            }

            $byPartner[$partner->id] = array_map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->supplierNumber,
                    'date' => $invoice->date,
                    'totalGross' => $invoice->totalGross,
                    'outstanding' => $this->outstandingForPurchase($invoice->totalGross, $invoice->id),
                ];
            }, $invoices);
        }

        return $byPartner;
    }

    private function insertTransactionRow(int $statementId, string $date, ?string $description, ?string $code, string $amount, string $direction, ?int $partnerId, int $glAccountId): int
    {
        if (!in_array($direction, BankTransaction::DIRECTIONS, true)) {
            throw new InvalidArgumentException('Изберете важечка насока (влез/излез).');
        }

        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Износот мора да биде поголем од нула.');
        }

        $amount = number_format((float) $amount, 2, '.', '');
        $previousBalance = $this->transactions->lastBalance($statementId);
        $balanceAfter = $direction === 'in' ? bcadd($previousBalance, $amount, 2) : bcsub($previousBalance, $amount, 2);

        return $this->transactions->create(new BankTransaction(
            $statementId,
            $date,
            $amount,
            $direction,
            $description ?: null,
            $code ?: null,
            $partnerId,
            $glAccountId,
            $balanceAfter
        ));
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
