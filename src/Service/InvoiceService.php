<?php

namespace App\Service;

use App\Core\Database;
use App\Domain\Invoicing\Invoice;
use App\Domain\Invoicing\InvoiceLine;
use App\Repository\AccountRepository;
use App\Repository\CurrencyRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\VatRateRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Сметките и ДДВ стапките никогаш не се бираат рачно на фактурата — се
 * резолвираат автоматски од категоријата на производот/услугата и од
 * контекстот (домашен/странски партнер). Види POSTING_RULES_ADDENDUM.md.
 */
class InvoiceService
{
    private const ACCOUNT_RECEIVABLES_DOMESTIC = '1200';
    private const ACCOUNT_RECEIVABLES_FOREIGN = '1201';

    private PDO $db;
    private InvoiceRepository $invoices;
    private PartnerRepository $partners;
    private ProductRepository $products;
    private ProductCategoryRepository $productCategories;
    private ServiceRepository $services;
    private ServiceCategoryRepository $serviceCategories;
    private VatRateRepository $vatRates;
    private AccountRepository $accounts;
    private CurrencyRepository $currencies;
    private LedgerService $ledger;

    public function __construct(
        ?InvoiceRepository $invoices = null,
        ?PartnerRepository $partners = null,
        ?ProductRepository $products = null,
        ?ProductCategoryRepository $productCategories = null,
        ?ServiceRepository $services = null,
        ?ServiceCategoryRepository $serviceCategories = null,
        ?VatRateRepository $vatRates = null,
        ?AccountRepository $accounts = null,
        ?LedgerService $ledger = null,
        ?CurrencyRepository $currencies = null
    ) {
        $this->db = Database::connection();
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->products = $products ?? new ProductRepository();
        $this->productCategories = $productCategories ?? new ProductCategoryRepository();
        $this->services = $services ?? new ServiceRepository();
        $this->serviceCategories = $serviceCategories ?? new ServiceCategoryRepository();
        $this->vatRates = $vatRates ?? new VatRateRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->currencies = $currencies ?? new CurrencyRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    /**
     * Создава фактура (статус draft). Секоја линија упатува на производ ИЛИ
     * услуга; сметката и ДДВ стапката се резолвираат тука (од категоријата +
     * контекст домашен/странски) и се замрзнуваат на линијата.
     *
     * Ставките (unit_price, line_total) и вкупните износи се во валутата
     * на фактурата (currencyId), не во MKD — конверзијата во MKD (главна
     * книга) се случува дури при issue().
     *
     * @param array<int, array{type: string, item_id: int, quantity: string|float, unit_price?: string|float|null, description?: ?string}> $lines
     */
    public function createInvoice(int $partnerId, string $date, string $dueDate, array $lines, ?int $currencyId = null, string $exchangeRate = '1.000000'): int
    {
        if (count($lines) < 1) {
            throw new InvalidArgumentException('Фактурата мора да содржи барем 1 ставка.');
        }

        $partner = $this->partners->find($partnerId);

        if (!$partner) {
            throw new InvalidArgumentException('Партнерот не е пронајден.');
        }

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

        $normalized = [];
        $totalNet = 0.0;
        $totalVat = 0.0;

        foreach ($lines as $line) {
            $type = $line['type'] ?? '';
            $itemId = (int) ($line['item_id'] ?? 0);
            $quantity = (float) ($line['quantity'] ?? 0);

            if ($itemId <= 0 || !in_array($type, ['product', 'service'], true)) {
                throw new InvalidArgumentException('Секоја ставка мора да има избрано производ или услуга.');
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Количината мора да биде поголема од нула.');
            }

            [$defaultPrice, $accountId, $vatRateId] = $this->resolveLine($type, $itemId, $partner->isForeign());

            $unitPriceInput = $line['unit_price'] ?? null;
            $unitPrice = ($unitPriceInput === null || $unitPriceInput === '')
                ? (float) $defaultPrice
                : (float) $unitPriceInput;

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Единечната цена не може да биде негативна.');
            }

            $vatRate = $this->vatRates->find($vatRateId);

            if (!$vatRate) {
                throw new RuntimeException('Резолвираната ДДВ стапка не постои.');
            }

            $lineTotal = round($quantity * $unitPrice, 2);
            $lineVat = round($lineTotal * (float) $vatRate->rate / 100, 2);

            $totalNet += $lineTotal;
            $totalVat += $lineVat;

            $normalized[] = [
                'product_id' => $type === 'product' ? $itemId : null,
                'service_id' => $type === 'service' ? $itemId : null,
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
            $invoice = new Invoice(
                $partnerId,
                $this->invoices->nextNumber(),
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
                $this->invoices->insertLine(new InvoiceLine(
                    $line['quantity'],
                    $line['unit_price'],
                    $line['account_id'],
                    $line['vat_rate_id'],
                    $line['vat_rate'],
                    $line['line_total'],
                    $line['product_id'],
                    $line['service_id'],
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

    /** @return array{0: string, 1: int, 2: int} [defaultPrice, accountId, vatRateId] */
    private function resolveLine(string $type, int $itemId, bool $isForeign): array
    {
        if ($type === 'product') {
            $product = $this->products->find($itemId);

            if (!$product) {
                throw new InvalidArgumentException('Избраниот производ не постои.');
            }

            $category = $this->productCategories->find($product->categoryId);

            if (!$category) {
                throw new RuntimeException('Категоријата на производот не постои.');
            }

            $resolved = $category->resolveFor($isForeign);

            return [$product->price, $resolved['account_id'], $resolved['vat_rate_id']];
        }

        $service = $this->services->find($itemId);

        if (!$service) {
            throw new InvalidArgumentException('Избраната услуга не постои.');
        }

        $category = $this->serviceCategories->find($service->categoryId);

        if (!$category) {
            throw new RuntimeException('Категоријата на услугата не постои.');
        }

        $resolved = $category->resolveFor($isForeign);

        return [$service->price, $resolved['account_id'], $resolved['vat_rate_id']];
    }

    /**
     * Ја издава фактурата. Книжењето се гради по алгоритмот од addendum-от:
     * 1 дебит ред (побарувања, бруто) + групирани кредит редови по сметка
     * (нето, по резолвирана приходна сметка) + групирани кредит редови по
     * ДДВ стапка (износ на ДДВ, по нејзината сметка за обврска).
     */
    public function issue(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Само фактура во статус „нацрт“ може да се издаде.');
        }

        $partner = $this->partners->find($invoice->partnerId);

        if (!$partner) {
            throw new RuntimeException('Партнерот на фактурата не постои.');
        }

        $receivablesCode = $partner->isForeign() ? self::ACCOUNT_RECEIVABLES_FOREIGN : self::ACCOUNT_RECEIVABLES_DOMESTIC;
        $receivablesAccount = $this->accounts->findByCode($receivablesCode);

        if (!$receivablesAccount) {
            throw new RuntimeException("Не постои стандардна сметка за побарувања ($receivablesCode) во контниот план.");
        }

        // Групирање по сметка/ДДВ стапка во валутата на документот, па
        // конверзија во MKD (главната книга е секогаш во денари) — курсот е
        // замрзнат на фактурата (invoice->exchangeRate), не се повторно бара.
        $revenueByAccount = [];
        $vatByRate = [];

        foreach ($invoice->lines as $line) {
            $revenueByAccount[$line->accountId] = bcadd($revenueByAccount[$line->accountId] ?? '0.00', $line->lineTotal, 2);

            $vatAmount = number_format($line->vatAmount(), 2, '.', '');
            $vatByRate[$line->vatRateId] = bcadd($vatByRate[$line->vatRateId] ?? '0.00', $vatAmount, 2);
        }

        $journalLines = [[
            'account_id' => $receivablesAccount->id,
            'partner_id' => $invoice->partnerId,
            'debit' => $invoice->grossInBaseCurrency(),
            'credit' => '0',
            'description' => "Фактура {$invoice->number}",
        ]];

        foreach ($revenueByAccount as $accountId => $amount) {
            $amount = bcmul($amount, $invoice->exchangeRate, 2);

            if (bccomp($amount, '0.00', 2) <= 0) {
                continue;
            }

            $journalLines[] = [
                'account_id' => $accountId,
                'debit' => '0',
                'credit' => $amount,
                'description' => "Фактура {$invoice->number}",
            ];
        }

        foreach ($vatByRate as $vatRateId => $amount) {
            $amount = bcmul($amount, $invoice->exchangeRate, 2);

            if (bccomp($amount, '0.00', 2) <= 0) {
                continue;
            }

            $vatRate = $this->vatRates->find($vatRateId);

            if (!$vatRate) {
                throw new RuntimeException('Резолвираната ДДВ стапка повеќе не постои.');
            }

            if (!$vatRate->payableAccountId) {
                throw new RuntimeException("ДДВ стапката „{$vatRate->name}“ нема поврзано сметка за обврска — не може да се книжи ДДВ.");
            }

            $journalLines[] = [
                'account_id' => $vatRate->payableAccountId,
                'debit' => '0',
                'credit' => $amount,
                'description' => "ДДВ по фактура {$invoice->number}",
            ];
        }

        if (count($journalLines) < 2) {
            throw new RuntimeException('Фактурата нема ставки за книжење.');
        }

        $entryId = $this->ledger->postEntry(
            $invoice->date,
            "Фактура {$invoice->number}",
            $invoice->number,
            $journalLines
        );

        $this->invoices->markIssued($invoiceId, $entryId);
    }

    public function markPaid(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'issued') {
            throw new RuntimeException('Само издадена фактура може да се означи како платена.');
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
            throw new RuntimeException('Само нацрт фактура може да се откаже (издадена бара сторно, идна фаза).');
        }

        $this->invoices->updateStatus($invoiceId, 'cancelled');
    }
}
