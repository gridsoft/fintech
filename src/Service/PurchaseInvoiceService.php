<?php

namespace App\Service;

use App\Core\Database;
use App\Domain\Invoicing\PurchaseInvoice;
use App\Domain\Invoicing\PurchaseInvoiceLine;
use App\Repository\AccountRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\PartnerRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Сметката за трошок/средство никогаш не се бира рачно — се резолвира
 * автоматски од категоријата на трошокот и од контекстот (домашен/странски
 * партнер), исто како InvoiceService на продажната страна. Разлика: ДДВ
 * стапката НЕ се резолвира од категоријата туку се внесува рачно по линија,
 * бидејќи е онаа што стои на примената фактура од добавувачот.
 * Види POSTING_RULES_ADDENDUM.md §5.
 *
 * Обратно оданочување (reverse_charge_applicable), капитализација
 * (is_capitalizable) и делумна одбивност на ДДВ (vat_deductible = 'partial')
 * се намерно надвор од опфат — секоја е засебен идентен чекор во addendum-от
 * (сек. „Што да се гради“, чекори 3-4) и бара сопствена постирачка патека
 * што сè уште не е изградена. Категорија со таков атрибут фрла грешка
 * наместо тивко да книжи погрешно.
 */
class PurchaseInvoiceService
{
    private const ACCOUNT_PAYABLES_DOMESTIC = '2200';
    private const ACCOUNT_PAYABLES_FOREIGN = '2201';

    private PDO $db;
    private PurchaseInvoiceRepository $invoices;
    private PartnerRepository $partners;
    private ExpenseCategoryRepository $expenseCategories;
    private VatRateRepository $vatRates;
    private AccountRepository $accounts;
    private LedgerService $ledger;

    public function __construct(
        ?PurchaseInvoiceRepository $invoices = null,
        ?PartnerRepository $partners = null,
        ?ExpenseCategoryRepository $expenseCategories = null,
        ?VatRateRepository $vatRates = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null
    ) {
        $this->db = Database::connection();
        $this->invoices = $invoices ?? new PurchaseInvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->expenseCategories = $expenseCategories ?? new ExpenseCategoryRepository();
        $this->vatRates = $vatRates ?? new VatRateRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    /**
     * Создава влезна фактура (статус draft). Секоја линија упатува на
     * категорија на трошок; сметката се резолвира тука (од категоријата +
     * контекст домашен/странски) и се замрзнува на линијата. ДДВ стапката
     * се презема од она што корисникот го внел (се чита од примената
     * фактура), не се резолвира автоматски.
     *
     * @param array<int, array{category_id: int, quantity: string|float, unit_price: string|float, vat_rate_id: int, description?: ?string}> $lines
     */
    public function createPurchaseInvoice(int $partnerId, string $supplierNumber, string $date, string $dueDate, array $lines): int
    {
        if (count($lines) < 1) {
            throw new InvalidArgumentException('Фактурата мора да содржи барем 1 ставка.');
        }

        $supplierNumber = trim($supplierNumber);

        if ($supplierNumber === '') {
            throw new InvalidArgumentException('Бројот на фактурата од добавувачот е задолжителен.');
        }

        $partner = $this->partners->find($partnerId);

        if (!$partner) {
            throw new InvalidArgumentException('Партнерот не е пронајден.');
        }

        if ($this->invoices->existsForPartnerAndNumber($partnerId, $supplierNumber)) {
            throw new InvalidArgumentException("Веќе постои внесена фактура бр. „{$supplierNumber}“ од овој партнер.");
        }

        $normalized = [];
        $totalNet = 0.0;
        $totalVat = 0.0;

        foreach ($lines as $line) {
            $categoryId = (int) ($line['category_id'] ?? 0);
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $vatRateId = (int) ($line['vat_rate_id'] ?? 0);

            if ($categoryId <= 0) {
                throw new InvalidArgumentException('Секоја ставка мора да има избрана категорија на трошок.');
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Количината мора да биде поголема од нула.');
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Единечната цена не може да биде негативна.');
            }

            $category = $this->expenseCategories->find($categoryId);

            if (!$category) {
                throw new InvalidArgumentException('Избраната категорија на трошок не постои.');
            }

            if ($category->reverseChargeApplicable) {
                throw new RuntimeException("Категоријата „{$category->name}“ бара обратно оданочување — тоа сè уште не е поддржано.");
            }

            if ($category->isCapitalizable) {
                throw new RuntimeException("Категоријата „{$category->name}“ е за основни средства — тоа сè уште не е поддржано, книжи преку идниот модул за основни средства.");
            }

            if ($category->vatDeductible === 'partial') {
                throw new RuntimeException("Категоријата „{$category->name}“ има делумна одбивност на ДДВ — тоа сè уште не е поддржано.");
            }

            $vatRate = $this->vatRates->find($vatRateId);

            if (!$vatRate) {
                throw new InvalidArgumentException('Изберете важечка ДДВ стапка за секоја ставка.');
            }

            $accountId = $category->resolveAccountFor($partner->isForeign());

            $lineTotal = round($quantity * $unitPrice, 2);
            $lineVat = round($lineTotal * (float) $vatRate->rate / 100, 2);

            $totalNet += $lineTotal;
            $totalVat += $lineVat;

            $normalized[] = [
                'expense_category_id' => $categoryId,
                'description' => trim((string) ($line['description'] ?? '')) ?: null,
                'quantity' => number_format($quantity, 2, '.', ''),
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'account_id' => $accountId,
                'vat_rate_id' => $vatRateId,
                'vat_rate' => $vatRate->rate,
                'line_total' => number_format($lineTotal, 2, '.', ''),
            ];
        }

        $totalNet = round($totalNet, 2);
        $totalVat = round($totalVat, 2);
        $totalGross = round($totalNet + $totalVat, 2);

        $this->db->beginTransaction();

        try {
            $invoice = new PurchaseInvoice(
                $partnerId,
                $supplierNumber,
                $date,
                $dueDate,
                'draft',
                number_format($totalNet, 2, '.', ''),
                number_format($totalVat, 2, '.', ''),
                number_format($totalGross, 2, '.', '')
            );

            $invoiceId = $this->invoices->create($invoice);

            foreach ($normalized as $line) {
                $this->invoices->insertLine(new PurchaseInvoiceLine(
                    $line['expense_category_id'],
                    $line['quantity'],
                    $line['unit_price'],
                    $line['account_id'],
                    $line['vat_rate_id'],
                    $line['vat_rate'],
                    $line['line_total'],
                    $line['description'],
                    $invoiceId
                ));
            }

            $this->db->commit();

            return $invoiceId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ја завидува (книжи) влезната фактура. Огледало на InvoiceService::issue(),
     * само дебит/кредит заменети: групирани дебит редови по резолвирана
     * сметка (трошок/средство), дебит по одбивен ДДВ, 1 кредит ред (обврски
     * кон добавувач, бруто). Кога категоријата на линијата е vat_deductible
     * = 'none', ДДВ износот не оди на посебна ДДВ сметка туку се додава на
     * трошокот (неодбивен ДДВ е дел од цената на чинење).
     */
    public function post(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Само фактура во статус „нацрт“ може да се заведе.');
        }

        $partner = $this->partners->find($invoice->partnerId);

        if (!$partner) {
            throw new RuntimeException('Партнерот на фактурата не постои.');
        }

        $payablesCode = $partner->isForeign() ? self::ACCOUNT_PAYABLES_FOREIGN : self::ACCOUNT_PAYABLES_DOMESTIC;
        $payablesAccount = $this->accounts->findByCode($payablesCode);

        if (!$payablesAccount) {
            throw new RuntimeException("Не постои стандардна сметка за обврски кон добавувачи ($payablesCode) во контниот план.");
        }

        $expenseByAccount = [];
        $vatByRate = [];

        foreach ($invoice->lines as $line) {
            $category = $this->expenseCategories->find($line->expenseCategoryId);

            if (!$category) {
                throw new RuntimeException('Категоријата на трошокот повеќе не постои.');
            }

            $vatAmount = number_format($line->vatAmount(), 2, '.', '');

            if ($category->vatDeductible === 'full') {
                $expenseByAccount[$line->accountId] = bcadd($expenseByAccount[$line->accountId] ?? '0.00', $line->lineTotal, 2);
                $vatByRate[$line->vatRateId] = bcadd($vatByRate[$line->vatRateId] ?? '0.00', $vatAmount, 2);
            } else {
                // 'none' — неодбивен ДДВ, влегува во трошокот (partial е веќе одбиено при createPurchaseInvoice).
                $expenseByAccount[$line->accountId] = bcadd($expenseByAccount[$line->accountId] ?? '0.00', bcadd($line->lineTotal, $vatAmount, 2), 2);
            }
        }

        $journalLines = [];

        foreach ($expenseByAccount as $accountId => $amount) {
            if (bccomp($amount, '0.00', 2) <= 0) {
                continue;
            }

            $journalLines[] = [
                'account_id' => $accountId,
                'debit' => $amount,
                'credit' => '0',
                'description' => "Влезна фактура {$invoice->supplierNumber}",
            ];
        }

        foreach ($vatByRate as $vatRateId => $amount) {
            if (bccomp($amount, '0.00', 2) <= 0) {
                continue;
            }

            $vatRate = $this->vatRates->find($vatRateId);

            if (!$vatRate) {
                throw new RuntimeException('Употребената ДДВ стапка повеќе не постои.');
            }

            if (!$vatRate->receivableAccountId) {
                throw new RuntimeException("ДДВ стапката „{$vatRate->name}“ нема поврзана сметка за влезен ДДВ — не може да се книжи.");
            }

            $journalLines[] = [
                'account_id' => $vatRate->receivableAccountId,
                'debit' => $amount,
                'credit' => '0',
                'description' => "Влезен ДДВ по фактура {$invoice->supplierNumber}",
            ];
        }

        $journalLines[] = [
            'account_id' => $payablesAccount->id,
            'partner_id' => $invoice->partnerId,
            'debit' => '0',
            'credit' => $invoice->totalGross,
            'description' => "Влезна фактура {$invoice->supplierNumber}",
        ];

        if (count($journalLines) < 2) {
            throw new RuntimeException('Фактурата нема ставки за книжење.');
        }

        $entryId = $this->ledger->postEntry(
            $invoice->date,
            "Влезна фактура {$invoice->supplierNumber}",
            $invoice->supplierNumber,
            $journalLines
        );

        $this->invoices->markPosted($invoiceId, $entryId);
    }

    public function markPaid(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'posted') {
            throw new RuntimeException('Само заведена фактура може да се означи како платена.');
        }

        $this->invoices->updateStatus($invoiceId, 'paid');
    }

    public function cancel(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Само нацрт фактура може да се откаже (заведена бара сторно, идна фаза).');
        }

        $this->invoices->updateStatus($invoiceId, 'cancelled');
    }
}
