<?php

namespace App\Service;

use App\Domain\Accounting\Account;
use App\Domain\Accounting\FxRevaluation;
use App\Domain\Accounting\FxRevaluationLine;
use App\Repository\AccountRepository;
use App\Repository\CurrencyRepository;
use App\Repository\FxRevaluationRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\PurchaseInvoiceRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Превалоризација (крај на период) на нереализирани курсни разлики за
 * отворените странски-валутни фактури — не менува ништо на самата фактура
 * (документот си останува во својот оригинален курс/валута), само ја
 * усогласува MKD книговодствената вредност на преостанатото салдо со нов
 * курс, преку GL-ниво прилагодување на AR/AP сметката.
 *
 * Клучно: "преостанатото во странска валута" секогаш се пресметува од
 * ОРИГИНАЛНИОТ курс на фактурата + вистинската историја на порамнување
 * (PaymentMatchingService::outstandingForSales/Purchase) — никогаш не се
 * менува со превалоризација. Само "тековно книгуваната MKD вредност" на тој
 * остаток се менува, и тоа секогаш во однос на ПОСЛЕДНАТА превалоризација
 * (FxRevaluationRepository::latestValueForInvoice), не од оригиналниот курс
 * — за да не се дуплира ефектот при повеќекратна превалоризација.
 */
class FxRevaluationService
{
    private const ACCOUNT_RECEIVABLES_DOMESTIC = '1200';
    private const ACCOUNT_RECEIVABLES_FOREIGN = '1201';
    private const ACCOUNT_PAYABLES_DOMESTIC = '2200';
    private const ACCOUNT_PAYABLES_FOREIGN = '2201';
    private const ACCOUNT_FX_GAIN = '7750';
    private const ACCOUNT_FX_LOSS = '4750';

    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PartnerRepository $partners;
    private CurrencyRepository $currencies;
    private AccountRepository $accounts;
    private FxRevaluationRepository $revaluations;
    private PaymentMatchingService $matching;
    private LedgerService $ledger;

    public function __construct(
        ?InvoiceRepository $invoices = null,
        ?PurchaseInvoiceRepository $purchaseInvoices = null,
        ?PartnerRepository $partners = null,
        ?CurrencyRepository $currencies = null,
        ?AccountRepository $accounts = null,
        ?FxRevaluationRepository $revaluations = null,
        ?PaymentMatchingService $matching = null,
        ?LedgerService $ledger = null
    ) {
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->purchaseInvoices = $purchaseInvoices ?? new PurchaseInvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->currencies = $currencies ?? new CurrencyRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->revaluations = $revaluations ?? new FxRevaluationRepository();
        $this->matching = $matching ?? new PaymentMatchingService();
        $this->ledger = $ledger ?? new LedgerService();
    }

    /**
     * Отворени фактури (издадени/заведени, со преостанато салдо > 0) во
     * дадена странска валута — за преглед пред да се изврши превалоризација.
     *
     * @return array<int, array{type: string, invoiceId: int, label: string, partnerId: int, remainingForeign: string, currentBookMkd: string}>
     */
    public function openInvoicesForCurrency(int $currencyId): array
    {
        $currency = $this->currencies->find($currencyId);

        if (!$currency || $currency->isBase) {
            throw new InvalidArgumentException('Изберете странска валута.');
        }

        $rows = [];

        foreach ($this->invoices->all() as $invoice) {
            if ($invoice->currencyId !== $currencyId || $invoice->status !== 'issued') {
                continue;
            }

            $outstandingMkd = $this->matching->outstandingForSales($invoice->grossInBaseCurrency(), $invoice->id);

            if (bccomp($outstandingMkd, '0.00', 2) <= 0) {
                continue;
            }

            $rows[] = [
                'type' => 'sales',
                'invoiceId' => $invoice->id,
                'label' => $invoice->number,
                'partnerId' => $invoice->partnerId,
                'remainingForeign' => bcdiv($outstandingMkd, $invoice->exchangeRate, 2),
                'currentBookMkd' => $this->revaluations->latestValueForInvoice('sales', $invoice->id) ?? $outstandingMkd,
            ];
        }

        foreach ($this->purchaseInvoices->all() as $invoice) {
            if ($invoice->currencyId !== $currencyId || $invoice->status !== 'posted') {
                continue;
            }

            $outstandingMkd = $this->matching->outstandingForPurchase($invoice->grossInBaseCurrency(), $invoice->id);

            if (bccomp($outstandingMkd, '0.00', 2) <= 0) {
                continue;
            }

            $rows[] = [
                'type' => 'purchase',
                'invoiceId' => $invoice->id,
                'label' => $invoice->supplierNumber,
                'partnerId' => $invoice->partnerId,
                'remainingForeign' => bcdiv($outstandingMkd, $invoice->exchangeRate, 2),
                'currentBookMkd' => $this->revaluations->latestValueForInvoice('purchase', $invoice->id) ?? $outstandingMkd,
            ];
        }

        return $rows;
    }

    /**
     * Ги превалоризира сите отворени фактури во $currencyId по $newRate,
     * ги книжи прилагодувањата (AR/AP по фактура + збирни курсни разлики) и
     * ги памти fx_revaluation_lines за следната превалоризација да смета од
     * оваа точка, не од оригиналниот курс на фактурата.
     */
    public function revalue(string $date, int $currencyId, string $newRate): int
    {
        if ((float) $newRate <= 0) {
            throw new InvalidArgumentException('Курсот мора да биде поголем од нула.');
        }

        $rows = $this->openInvoicesForCurrency($currencyId);

        if (!$rows) {
            throw new RuntimeException('Нема отворени фактури во оваа валута за превалоризација.');
        }

        $gainAccount = $this->accounts->findByCode(self::ACCOUNT_FX_GAIN);
        $lossAccount = $this->accounts->findByCode(self::ACCOUNT_FX_LOSS);

        if (!$gainAccount || !$lossAccount) {
            throw new RuntimeException('Не постојат сметки за курсни разлики во контниот план.');
        }

        $journalLines = [];
        $totalGain = '0.00';
        $totalLoss = '0.00';
        $lineRecords = [];

        foreach ($rows as $row) {
            $newMkd = bcmul($row['remainingForeign'], $newRate, 2);
            $delta = bcsub($newMkd, $row['currentBookMkd'], 2);

            if (bccomp($delta, '0.00', 2) === 0) {
                continue;
            }

            $partner = $this->partners->find($row['partnerId']);
            $arApAccount = $this->resolveArApAccount($row['type'], $partner ? $partner->isForeign() : true);
            $gainForRow = $row['type'] === 'sales' ? $delta : bcsub('0.00', $delta, 2);

            // AR (дебит-нормална) расте со позитивна delta; AP (кредит-нормална) расте со позитивна delta.
            $increases = bccomp($delta, '0.00', 2) > 0;
            $amount = $increases ? $delta : bcsub('0.00', $delta, 2);
            $isArSide = $row['type'] === 'sales';

            $journalLines[] = [
                'account_id' => $arApAccount->id,
                'partner_id' => $row['partnerId'],
                'debit' => ($isArSide === $increases) ? $amount : '0',
                'credit' => ($isArSide === $increases) ? '0' : $amount,
                'description' => "Превалоризација — {$row['label']}",
            ];

            if (bccomp($gainForRow, '0.00', 2) > 0) {
                $totalGain = bcadd($totalGain, $gainForRow, 2);
            } else {
                $totalLoss = bcadd($totalLoss, bcsub('0.00', $gainForRow, 2), 2);
            }

            $lineRecords[] = [
                'type' => $row['type'],
                'invoiceId' => $row['invoiceId'],
                'before' => $row['currentBookMkd'],
                'after' => $newMkd,
                'difference' => $gainForRow,
            ];
        }

        if (!$journalLines) {
            throw new RuntimeException('Нема разлика за книжење — сите отворени фактури се веќе усогласени со овој курс.');
        }

        if (bccomp($totalGain, '0.00', 2) > 0) {
            $journalLines[] = ['account_id' => $gainAccount->id, 'debit' => '0', 'credit' => $totalGain, 'description' => 'Превалоризација — курсна разлика (добивка)'];
        }

        if (bccomp($totalLoss, '0.00', 2) > 0) {
            $journalLines[] = ['account_id' => $lossAccount->id, 'debit' => $totalLoss, 'credit' => '0', 'description' => 'Превалоризација — курсна разлика (загуба)'];
        }

        // postEntry() веќе ја опфаќа сопствената трансакција (запис + редови) — не се вгнездува уште една тука,
        // исто како PurchaseInvoiceService::post() (LedgerService::postEntry() + markPosted() без надворешен wrap).
        $entryId = $this->ledger->postEntry($date, 'Превалоризација на курсни разлики', null, $journalLines);

        $revaluationId = $this->revaluations->create(new FxRevaluation($date, $currencyId, $newRate, $entryId));

        foreach ($lineRecords as $record) {
            $this->revaluations->insertLine(new FxRevaluationLine(
                $revaluationId,
                $record['type'],
                $record['invoiceId'],
                $record['before'],
                $record['after'],
                $record['difference']
            ));
        }

        return $revaluationId;
    }

    private function resolveArApAccount(string $type, bool $isForeignPartner): Account
    {
        if ($type === 'sales') {
            $code = $isForeignPartner ? self::ACCOUNT_RECEIVABLES_FOREIGN : self::ACCOUNT_RECEIVABLES_DOMESTIC;
        } else {
            $code = $isForeignPartner ? self::ACCOUNT_PAYABLES_FOREIGN : self::ACCOUNT_PAYABLES_DOMESTIC;
        }

        $account = $this->accounts->findByCode($code);

        if (!$account) {
            throw new RuntimeException("Не постои стандардна сметка ($code) во контниот план.");
        }

        return $account;
    }
}
