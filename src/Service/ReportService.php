<?php

namespace App\Service;

use App\Repository\AccountRepository;
use App\Repository\JournalRepository;
use App\Repository\PartnerRepository;

class ReportService
{
    private const ACCOUNT_VAT_OUTPUT = '4300';
    private const ACCOUNT_VAT_INPUT = '2450';

    private JournalRepository $journal;
    private AccountRepository $accounts;
    private PartnerRepository $partners;

    public function __construct(
        ?JournalRepository $journal = null,
        ?AccountRepository $accounts = null,
        ?PartnerRepository $partners = null
    ) {
        $this->journal = $journal ?? new JournalRepository();
        $this->accounts = $accounts ?? new AccountRepository();
        $this->partners = $partners ?? new PartnerRepository();
    }

    /**
     * Бруто биланс: збир на дебит/кредит по сметка (само сметки со активност).
     *
     * @return array{rows: array<int, array{account: \App\Domain\Accounting\Account, debit: string, credit: string}>, totalDebit: string, totalCredit: string, balanced: bool}
     */
    public function trialBalance(): array
    {
        $balances = $this->journal->balancesByAccount();
        $accountsById = $this->accountsById();

        $rows = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($balances as $balance) {
            $account = $accountsById[(int) $balance['account_id']] ?? null;

            if (!$account) {
                continue;
            }

            $rows[] = [
                'account' => $account,
                'debit' => $balance['debit'],
                'credit' => $balance['credit'],
            ];

            $totalDebit = bcadd($totalDebit, $balance['debit'], 2);
            $totalCredit = bcadd($totalCredit, $balance['credit'], 2);
        }

        usort($rows, fn ($a, $b) => strcmp($a['account']->code, $b['account']->code));

        return [
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balanced' => bccomp($totalDebit, $totalCredit, 2) === 0,
        ];
    }

    /**
     * Едноставна ДДВ евиденција за период: излезен ДДВ (сметка 4300, кредит) наспроти
     * влезен ДДВ (сметка 2450, дебит — сè уште нема модул што книжи тука, Фаза 6).
     *
     * @return array{from: string, to: string, outputVat: string, inputVat: string, netPayable: string}
     */
    public function vatSummary(string $from, string $to): array
    {
        $output = $this->journal->sumForAccountCodeInPeriod(self::ACCOUNT_VAT_OUTPUT, $from, $to);
        $input = $this->journal->sumForAccountCodeInPeriod(self::ACCOUNT_VAT_INPUT, $from, $to);

        $outputVat = $output['credit'] ?? '0.00';
        $inputVat = $input['debit'] ?? '0.00';

        return [
            'from' => $from,
            'to' => $to,
            'outputVat' => $outputVat,
            'inputVat' => $inputVat,
            'netPayable' => bcsub($outputVat, $inputVat, 2),
        ];
    }

    /**
     * Отворени ставки по партнер (салдо != 0). Позитивно салдо = партнерот ни должи.
     *
     * @return array<int, array{partner: \App\Domain\Partners\Partner, debit: string, credit: string, balance: string}>
     */
    public function openItemsByPartner(): array
    {
        $balances = $this->journal->balancesByPartner();
        $partnersById = $this->partnersById();

        $rows = [];

        foreach ($balances as $balance) {
            $partner = $partnersById[(int) $balance['partner_id']] ?? null;

            if (!$partner) {
                continue;
            }

            $net = bcsub($balance['debit'], $balance['credit'], 2);

            if (bccomp($net, '0.00', 2) === 0) {
                continue;
            }

            $rows[] = [
                'partner' => $partner,
                'debit' => $balance['debit'],
                'credit' => $balance['credit'],
                'balance' => $net,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['partner']->name, $b['partner']->name));

        return $rows;
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
