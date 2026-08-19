<?php

namespace App\Service;

use App\Core\Database;
use App\Domain\Invoicing\PurchaseInvoice;
use App\Domain\Invoicing\PurchaseInvoiceLine;
use App\Domain\Partners\Partner;
use App\Repository\AccountRepository;
use App\Repository\AdvanceApplicationRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\CurrencyRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\FixedAssetRepository;
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
 * Обратно оданочување (reverse_charge_applicable) и делумна одбивност на
 * ДДВ (vat_deductible = 'partial') се намерно надвор од опфат — секоја е
 * засебен идентен чекор во addendum-от (сек. „Што да се гради“, чекор 3) и
 * бара сопствена постирачка патека што сè уште не е изградена. Категорија
 * со таков атрибут фрла грешка наместо тивко да книжи погрешно.
 * Капитализација (is_capitalizable) е изградена во Фаза 8 — види post().
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
    private CurrencyRepository $currencies;
    private LedgerService $ledger;
    private FixedAssetService $fixedAssets;
    private BankTransactionRepository $bankTransactions;
    private AdvanceApplicationRepository $advanceApplications;
    private FixedAssetRepository $fixedAssetsRepository;

    public function __construct(
        ?PurchaseInvoiceRepository $invoices = null,
        ?PartnerRepository $partners = null,
        ?ExpenseCategoryRepository $expenseCategories = null,
        ?VatRateRepository $vatRates = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null,
        ?FixedAssetService $fixedAssets = null,
        ?CurrencyRepository $currencies = null,
        ?BankTransactionRepository $bankTransactions = null,
        ?AdvanceApplicationRepository $advanceApplications = null,
        ?FixedAssetRepository $fixedAssetsRepository = null
    ) {
        $this->db = Database::connection();
        $this->invoices = $invoices ?? new PurchaseInvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->expenseCategories = $expenseCategories ?? new ExpenseCategoryRepository();
        $this->vatRates = $vatRates ?? new VatRateRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->currencies = $currencies ?? new CurrencyRepository();
        $this->ledger = $ledger ?? new LedgerService();
        $this->fixedAssets = $fixedAssets ?? new FixedAssetService();
        $this->bankTransactions = $bankTransactions ?? new BankTransactionRepository();
        $this->advanceApplications = $advanceApplications ?? new AdvanceApplicationRepository();
        $this->fixedAssetsRepository = $fixedAssetsRepository ?? new FixedAssetRepository();
    }

    /**
     * Создава влезна фактура (статус draft). Секоја линија упатува на
     * категорија на трошок; сметката се резолвира тука (од категоријата +
     * контекст домашен/странски) и се замрзнува на линијата. ДДВ стапката
     * се презема од она што корисникот го внел (се чита од примената
     * фактура), не се резолвира автоматски.
     *
     * Ставките (unit_price, line_total) и вкупните износи се во валутата
     * на фактурата (currencyId), не во MKD — конверзијата во MKD (главна
     * книга) се случува дури при post().
     *
     * @param array<int, array{category_id: int, quantity: string|float, unit_price: string|float, vat_rate_id: int, description?: ?string}> $lines
     */
    public function createPurchaseInvoice(int $partnerId, string $supplierNumber, string $date, string $dueDate, array $lines, ?int $currencyId = null, string $exchangeRate = '1.000000'): int
    {
        $supplierNumber = trim($supplierNumber);

        if ($supplierNumber === '') {
            throw new InvalidArgumentException('Бројот на фактурата од добавувачот е задолжителен.');
        }

        $partner = $this->partners->find($partnerId);

        if (!$partner) {
            throw new InvalidArgumentException('Партнерот не е пронајден.');
        }

        $currency = $this->resolveCurrency($currencyId, $exchangeRate);
        $exchangeRate = $currency['exchangeRate'];
        $currency = $currency['currency'];

        if ($this->invoices->existsForPartnerAndNumber($partnerId, $supplierNumber)) {
            throw new InvalidArgumentException("Веќе постои внесена фактура бр. „{$supplierNumber}“ од овој партнер.");
        }

        [$normalized, $totalNet, $totalVat] = $this->normalizeLines($lines, $partner);
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
                number_format($totalGross, 2, '.', ''),
                null,
                null,
                $currency->id,
                $exchangeRate
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
     * Уредува влезна фактура. Дозволено на `draft` (без ефект врз главната
     * книга) И на `posted` (ама ТОГАШ задолжително прекнижува — сторнира
     * старото книжење и книжи ново според изменетите линии, огледало на
     * InvoiceService::updateInvoice(), исто образложение во DECISIONS.md).
     * Заведена фактура е уредлива само ако сè уште нема НИШТО реално
     * поврзано со неа: матчирана банкарска трансакција, применет аванс, или
     * веќе создадено основно средство (вредноста таму е замрзната еднаш,
     * прекнижувањето не би можело да ја усогласи) — assertEditablePostedInvoice().
     *
     * @param array<int, array{category_id: int, quantity: string|float, unit_price: string|float, vat_rate_id: int, description?: ?string}> $lines
     */
    public function updatePurchaseInvoice(int $invoiceId, int $partnerId, string $supplierNumber, string $date, string $dueDate, array $lines, ?int $currencyId = null, string $exchangeRate = '1.000000'): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if (!in_array($invoice->status, ['draft', 'posted'], true)) {
            throw new RuntimeException('Само нацрт или заведена фактура може да се уредува (платена/откажана — не).');
        }

        $wasPosted = $invoice->status === 'posted';

        if ($wasPosted) {
            $this->assertEditablePostedInvoice($invoice);
        }

        $supplierNumber = trim($supplierNumber);

        if ($supplierNumber === '') {
            throw new InvalidArgumentException('Бројот на фактурата од добавувачот е задолжителен.');
        }

        $partner = $this->partners->find($partnerId);

        if (!$partner) {
            throw new InvalidArgumentException('Партнерот не е пронајден.');
        }

        $currency = $this->resolveCurrency($currencyId, $exchangeRate);
        $exchangeRate = $currency['exchangeRate'];
        $currency = $currency['currency'];

        if ($this->invoices->existsForPartnerAndNumber($partnerId, $supplierNumber, $invoiceId)) {
            throw new InvalidArgumentException("Веќе постои внесена фактура бр. „{$supplierNumber}“ од овој партнер.");
        }

        [$normalized, $totalNet, $totalVat] = $this->normalizeLines($lines, $partner);
        $totalGross = round($totalNet + $totalVat, 2);

        $this->db->beginTransaction();

        try {
            $this->invoices->updateHeader(
                $invoiceId,
                $partnerId,
                $supplierNumber,
                $date,
                $dueDate,
                $currency->id,
                $exchangeRate,
                number_format($totalNet, 2, '.', ''),
                number_format($totalVat, 2, '.', ''),
                number_format($totalGross, 2, '.', '')
            );

            $this->invoices->deleteLines($invoiceId);

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
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        // Исто ограничување како InvoiceService::updateInvoice(): reverseEntry()/
        // postEntry() секој управува со сопствена транзакција, не вгнездени
        // во горната — веќе прифатено од post() (postEntry па markPosted/
        // fixedAssets како засебни чекори), не ново овде.
        if ($wasPosted) {
            if ($invoice->journalEntryId) {
                $this->ledger->reverseEntry(
                    $invoice->journalEntryId,
                    $invoice->date,
                    "Сторно пред уредување на влезна фактура {$invoice->supplierNumber}",
                    $invoice->supplierNumber
                );
            }

            $refreshed = $this->invoices->find($invoiceId);
            [$journalLines, $capitalizableLines] = $this->buildPostingPlan($refreshed, $partner);

            $newEntryId = $this->ledger->postEntry(
                $refreshed->date,
                "Влезна фактура {$refreshed->supplierNumber}",
                $refreshed->supplierNumber,
                $journalLines
            );
            $this->invoices->markPosted($invoiceId, $newEntryId);

            foreach ($capitalizableLines as $capitalizable) {
                $this->fixedAssets->createFromPurchaseInvoiceLine(
                    $invoiceId,
                    $refreshed->supplierNumber,
                    $capitalizable['line'],
                    $capitalizable['category'],
                    $refreshed->date,
                    $refreshed->exchangeRate
                );
            }
        }
    }

    /**
     * Причината зошто веќе заведена фактура НЕ може да се уредува, или null
     * ако може — јавно, за контролерот да провери и на GET /edit (пред да
     * прикаже форма што секако би отпаднала на зачувување). Секоја причина е
     * реална последица што прекнижувањето не би можело тивко да ја усогласи
     * (анализа во разговорот/DECISIONS.md).
     */
    public function postedInvoiceEditBlockReason(PurchaseInvoice $invoice): ?string
    {
        if (bccomp($this->bankTransactions->matchedAmountForInvoice('purchase', $invoice->id), '0.00', 2) > 0) {
            return 'Фактурата има поврзана банкарска трансакција (исплата) — не може да се уредува.';
        }

        if (bccomp($this->advanceApplications->appliedAmountForInvoice('purchase', $invoice->id), '0.00', 2) > 0) {
            return 'Фактурата има применет аванс — не може да се уредува.';
        }

        if ($this->fixedAssetsRepository->existsForPurchaseInvoice($invoice->id)) {
            return 'Фактурата веќе создаде основно средство (вредноста таму е замрзната) — не може да се уредува.';
        }

        return null;
    }

    private function assertEditablePostedInvoice(PurchaseInvoice $invoice): void
    {
        $reason = $this->postedInvoiceEditBlockReason($invoice);

        if ($reason !== null) {
            throw new RuntimeException($reason);
        }
    }

    /** @return array{currency: \App\Domain\Accounting\Currency, exchangeRate: string} */
    private function resolveCurrency(?int $currencyId, string $exchangeRate): array
    {
        $baseCurrency = $this->currencies->base();
        $currency = $currencyId !== null ? $this->currencies->find($currencyId) : $baseCurrency;

        if (!$currency) {
            throw new InvalidArgumentException('Изберете важечка валута.');
        }

        if ($currency->isBase) {
            $exchangeRate = '1.000000';
        } elseif ((float) $exchangeRate <= 0) {
            throw new InvalidArgumentException('Курсот мора да биде поголем од нула.');
        } else {
            $exchangeRate = number_format((float) $exchangeRate, 6, '.', '');
        }

        return ['currency' => $currency, 'exchangeRate' => $exchangeRate];
    }

    /**
     * @param array<int, array{category_id: int, quantity: string|float, unit_price: string|float, vat_rate_id: int, description?: ?string}> $lines
     * @return array{0: array<int, array<string, mixed>>, 1: float, 2: float} [normalizedLines, totalNet, totalVat]
     */
    private function normalizeLines(array $lines, Partner $partner): array
    {
        if (count($lines) < 1) {
            throw new InvalidArgumentException('Фактурата мора да содржи барем 1 ставка.');
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

            if ($category->isCapitalizable && !$category->defaultAnnualRate) {
                throw new RuntimeException("Категоријата „{$category->name}“ е основно средство без поставена стапка на амортизација — уреди ја категоријата пред да ја користиш.");
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

        return [$normalized, round($totalNet, 2), round($totalVat, 2)];
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

        [$journalLines, $capitalizableLines] = $this->buildPostingPlan($invoice, $partner);

        $entryId = $this->ledger->postEntry(
            $invoice->date,
            "Влезна фактура {$invoice->supplierNumber}",
            $invoice->supplierNumber,
            $journalLines
        );

        $this->invoices->markPosted($invoiceId, $entryId);

        foreach ($capitalizableLines as $capitalizable) {
            $this->fixedAssets->createFromPurchaseInvoiceLine(
                $invoiceId,
                $invoice->supplierNumber,
                $capitalizable['line'],
                $capitalizable['category'],
                $invoice->date,
                $invoice->exchangeRate
            );
        }
    }

    /**
     * Групирани дебит редови по сметка (трошок/средство) + дебит по одбивен
     * ДДВ + 1 кредит ред (обврски кон добавувач, бруто), плус списокот на
     * капитализабилни линии (за создавање основни средства). Иста логика се
     * користи и при прво заведување и при прекнижување по уредување на веќе
     * заведена фактура (updatePurchaseInvoice()) — секогаш гради од
     * тековните (свежи) линии.
     *
     * @return array{0: array<int, array{account_id: int, partner_id?: int, debit: string, credit: string, description: string}>, 1: array<int, array{line: \App\Domain\Invoicing\PurchaseInvoiceLine, category: \App\Domain\Invoicing\ExpenseCategory}>}
     */
    private function buildPostingPlan(PurchaseInvoice $invoice, Partner $partner): array
    {
        $payablesCode = $partner->isForeign() ? self::ACCOUNT_PAYABLES_FOREIGN : self::ACCOUNT_PAYABLES_DOMESTIC;
        $payablesAccount = $this->accounts->findByCode($payablesCode);

        if (!$payablesAccount) {
            throw new RuntimeException("Не постои стандардна сметка за обврски кон добавувачи ($payablesCode) во контниот план.");
        }

        // Групирање по сметка/ДДВ стапка во валутата на документот, па
        // конверзија во MKD (главната книга е секогаш во денари) — курсот е
        // замрзнат на фактурата (invoice->exchangeRate), не се повторно бара.
        $expenseByAccount = [];
        $vatByRate = [];
        $capitalizableLines = [];

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

            if ($category->isCapitalizable) {
                $capitalizableLines[] = ['line' => $line, 'category' => $category];
            }
        }

        $journalLines = [];

        foreach ($expenseByAccount as $accountId => $amount) {
            $amount = bcmul($amount, $invoice->exchangeRate, 2);

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
            $amount = bcmul($amount, $invoice->exchangeRate, 2);

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
            'credit' => $invoice->grossInBaseCurrency(),
            'description' => "Влезна фактура {$invoice->supplierNumber}",
        ];

        if (count($journalLines) < 2) {
            throw new RuntimeException('Фактурата нема ставки за книжење.');
        }

        return [$journalLines, $capitalizableLines];
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
