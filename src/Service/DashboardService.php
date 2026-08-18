<?php

namespace App\Service;

use App\Repository\AccountRepository;
use App\Repository\InvoiceRepository;
use App\Repository\JournalRepository;
use App\Repository\PurchaseInvoiceRepository;
use DateTimeImmutable;

/**
 * Ги подготвува бројките за контролната табла — KPI-и, месечен тренд на
 * приход/трошок и остарување на побарувањата. Чисто читање/агрегација, не
 * книжи ништо. Приходот/трошокот се сметаат од нето износот (без ДДВ),
 * конвертиран во MKD по курсот замрзнат на секоја фактура.
 */
class DashboardService
{
    private const MONTH_NAMES = [
        1 => 'јан', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'мај', 6 => 'јун',
        7 => 'јул', 8 => 'авг', 9 => 'сеп', 10 => 'окт', 11 => 'ное', 12 => 'дек',
    ];

    private AccountRepository $accounts;
    private JournalRepository $journal;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PaymentMatchingService $matching;

    public function __construct(
        ?AccountRepository $accounts = null,
        ?JournalRepository $journal = null,
        ?InvoiceRepository $invoices = null,
        ?PurchaseInvoiceRepository $purchaseInvoices = null,
        ?PaymentMatchingService $matching = null
    ) {
        $this->accounts = $accounts ?? new AccountRepository();
        $this->journal = $journal ?? new JournalRepository();
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->purchaseInvoices = $purchaseInvoices ?? new PurchaseInvoiceRepository();
        $this->matching = $matching ?? new PaymentMatchingService();
    }

    /**
     * @return array{
     *     cashBalance: string,
     *     openReceivables: string,
     *     openPayables: string,
     *     revenueThisMonth: string,
     *     trend: array{labels: string[], revenue: string[], expense: string[]},
     *     arAging: array{labels: string[], values: string[]}
     * }
     */
    public function summary(): array
    {
        $cashAccountIds = array_map(fn ($a) => $a->id, $this->accounts->cashAccounts());
        $cashBalance = $this->journal->balanceForAccountIds($cashAccountIds);

        $openInvoices = $this->invoices->openIssued();
        $openPurchaseInvoices = $this->purchaseInvoices->openPosted();

        $openReceivables = '0.00';
        $arAgingBuckets = ['Тековно' => '0.00', '1–30 дена' => '0.00', '31–60 дена' => '0.00', 'Над 60 дена' => '0.00'];
        $today = new DateTimeImmutable('today');

        foreach ($openInvoices as $invoice) {
            $outstanding = $this->matching->outstandingForSales($invoice->grossInBaseCurrency(), $invoice->id);

            if (bccomp($outstanding, '0.00', 2) <= 0) {
                continue;
            }

            $openReceivables = bcadd($openReceivables, $outstanding, 2);

            $daysOverdue = $today->diff(new DateTimeImmutable($invoice->dueDate))->days;
            $isOverdue = new DateTimeImmutable($invoice->dueDate) < $today;
            $bucket = !$isOverdue ? 'Тековно' : ($daysOverdue <= 30 ? '1–30 дена' : ($daysOverdue <= 60 ? '31–60 дена' : 'Над 60 дена'));

            $arAgingBuckets[$bucket] = bcadd($arAgingBuckets[$bucket], $outstanding, 2);
        }

        $openPayables = '0.00';

        foreach ($openPurchaseInvoices as $invoice) {
            $outstanding = $this->matching->outstandingForPurchase($invoice->grossInBaseCurrency(), $invoice->id);

            if (bccomp($outstanding, '0.00', 2) > 0) {
                $openPayables = bcadd($openPayables, $outstanding, 2);
            }
        }

        $trend = $this->monthlyTrend(6);
        $currentMonthKey = (new DateTimeImmutable('first day of this month'))->format('Y-m');
        $revenueThisMonth = $trend['revenueByMonth'][$currentMonthKey] ?? '0.00';

        return [
            'cashBalance' => $cashBalance,
            'openReceivables' => $openReceivables,
            'openPayables' => $openPayables,
            'revenueThisMonth' => $revenueThisMonth,
            'trend' => ['labels' => $trend['labels'], 'revenue' => $trend['revenue'], 'expense' => $trend['expense']],
            'arAging' => ['labels' => array_keys($arAgingBuckets), 'values' => array_values($arAgingBuckets)],
        ];
    }

    /**
     * @return array{labels: string[], revenue: string[], expense: string[], revenueByMonth: array<string, string>}
     */
    private function monthlyTrend(int $months): array
    {
        $since = (new DateTimeImmutable("first day of -" . ($months - 1) . " months"))->format('Y-m-01');

        $revenueByMonth = array_column($this->invoices->monthlyRevenueTotals($since), 'total', 'ym');
        $expenseByMonth = array_column($this->purchaseInvoices->monthlyExpenseTotals($since), 'total', 'ym');

        $labels = [];
        $revenue = [];
        $expense = [];
        $revenueByMonthLabeled = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = new DateTimeImmutable("first day of -$i months");
            $key = $month->format('Y-m');
            $label = self::MONTH_NAMES[(int) $month->format('n')] . ' ' . $month->format('y');

            $labels[] = $label;
            $revenue[] = number_format((float) ($revenueByMonth[$key] ?? 0), 2, '.', '');
            $expense[] = number_format((float) ($expenseByMonth[$key] ?? 0), 2, '.', '');
            $revenueByMonthLabeled[$key] = number_format((float) ($revenueByMonth[$key] ?? 0), 2, '.', '');
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'expense' => $expense, 'revenueByMonth' => $revenueByMonthLabeled];
    }
}
