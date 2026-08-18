<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\CurrencyRepository;
use App\Repository\FxRevaluationRepository;
use App\Repository\PartnerRepository;
use App\Service\FxRevaluationService;
use InvalidArgumentException;
use RuntimeException;

class FxRevaluationController
{
    private CurrencyRepository $currencies;
    private FxRevaluationRepository $revaluations;
    private PartnerRepository $partners;
    private FxRevaluationService $service;

    public function __construct()
    {
        $this->currencies = new CurrencyRepository();
        $this->revaluations = new FxRevaluationRepository();
        $this->partners = new PartnerRepository();
        $this->service = new FxRevaluationService();
    }

    public function index(Request $request): void
    {
        $foreignCurrencies = array_values(array_filter($this->currencies->allActive(), fn ($c) => !$c->isBase));
        $currencyId = $request->input('currency_id') ?: ($foreignCurrencies[0]->id ?? null);

        $openInvoices = $currencyId ? $this->service->openInvoicesForCurrency((int) $currencyId) : [];
        $partnersById = $this->partnersById();

        Response::view('fx-revaluations/index', [
            'pageTitle' => 'Курсни разлики — превалоризација',
            'activeNav' => 'fx-revaluations',
            'breadcrumb' => ['Почетна' => '/', 'Курсни разлики'],
            'foreignCurrencies' => $foreignCurrencies,
            'selectedCurrencyId' => $currencyId ? (int) $currencyId : null,
            'openInvoices' => $openInvoices,
            'partnersById' => $partnersById,
            'history' => $this->revaluations->all(),
            'currenciesById' => $this->currenciesById(),
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $currencyId = $request->input('currency_id');
        $date = $request->input('date');
        $newRate = (string) $request->input('new_rate');

        if (!$currencyId || !$date || !$newRate) {
            Response::html('<h1>Грешка</h1><p>Пополнете валута, датум и курс.</p><p><a href="/fx-revaluations">Назад</a></p>', 422);
            return;
        }

        try {
            $this->service->revalue($date, (int) $currencyId, $newRate);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/fx-revaluations?currency_id=' . (int) $currencyId . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect('/fx-revaluations?currency_id=' . (int) $currencyId);
    }

    /** @return array<int, \App\Domain\Partners\Partner> */
    private function partnersById(): array
    {
        $partners = $this->partners->all();

        return array_combine(array_map(fn ($p) => $p->id, $partners), $partners);
    }

    /** @return array<int, \App\Domain\Accounting\Currency> */
    private function currenciesById(): array
    {
        $currencies = $this->currencies->all();

        return array_combine(array_map(fn ($c) => $c->id, $currencies), $currencies);
    }
}
