<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Accounting\Currency;
use App\Repository\CurrencyRepository;

/**
 * MKD (базната валута) е фиксна — внесена во migration 019, не се уредува
 * ниту брише тука. Овде се управуваат само дополнителните странски валути
 * во кои клиентот смее да фактурира (EUR, доколку затребаат и други).
 */
class CurrencyController
{
    private CurrencyRepository $currencies;

    public function __construct()
    {
        $this->currencies = new CurrencyRepository();
    }

    public function index(Request $request): void
    {
        Response::view('currencies/index', [
            'pageTitle' => 'Валути',
            'activeNav' => 'currencies',
            'breadcrumb' => ['Почетна' => '/', 'Валути'],
            'currencies' => $this->currencies->all(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('currencies/form', [
            'pageTitle' => 'Нова валута',
            'activeNav' => 'currencies',
            'breadcrumb' => ['Почетна' => '/', 'Валути' => '/currencies', 'Нова валута'],
            'currency' => null,
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request, null);

        if ($errors) {
            Response::view('currencies/form', [
                'pageTitle' => 'Нова валута',
                'activeNav' => 'currencies',
                'breadcrumb' => ['Почетна' => '/', 'Валути' => '/currencies', 'Нова валута'],
                'currency' => null,
                'errors' => $errors,
            ]);
            return;
        }

        $this->currencies->create(new Currency(
            strtoupper(trim($request->input('code'))),
            trim($request->input('name')),
            false,
            $request->input('is_active') === '1'
        ));

        Response::redirect('/currencies');
    }

    public function edit(Request $request, string $id): void
    {
        $currency = $this->currencies->find((int) $id);

        if (!$currency || $currency->isBase) {
            Response::html('<h1>404</h1><p>Валутата не е пронајдена.</p>', 404);
            return;
        }

        Response::view('currencies/form', [
            'pageTitle' => 'Уреди валута',
            'activeNav' => 'currencies',
            'breadcrumb' => ['Почетна' => '/', 'Валути' => '/currencies', 'Уреди валута'],
            'currency' => $currency,
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $currency = $this->currencies->find((int) $id);

        if (!$currency || $currency->isBase) {
            Response::html('<h1>404</h1><p>Валутата не е пронајдена.</p>', 404);
            return;
        }

        $errors = $this->validate($request, $currency->id);

        if ($errors) {
            Response::view('currencies/form', [
                'pageTitle' => 'Уреди валута',
                'activeNav' => 'currencies',
                'breadcrumb' => ['Почетна' => '/', 'Валути' => '/currencies', 'Уреди валута'],
                'currency' => $currency,
                'errors' => $errors,
            ]);
            return;
        }

        $currency->name = trim($request->input('name'));
        $currency->isActive = $request->input('is_active') === '1';

        $this->currencies->update($currency);

        Response::redirect('/currencies');
    }

    public function destroy(Request $request, string $id): void
    {
        $currencyId = (int) $id;
        $currency = $this->currencies->find($currencyId);

        if (!$currency || $currency->isBase) {
            Response::html('<h1>Грешка</h1><p>Оваа валута не може да се избрише.</p><p><a href="/currencies">Назад</a></p>', 422);
            return;
        }

        if ($this->currencies->isInUse($currencyId)) {
            Response::html('<h1>Грешка</h1><p>Оваа валута се користи на фактура и не може да се избрише. Деактивирај ја наместо тоа.</p><p><a href="/currencies">Назад</a></p>', 422);
            return;
        }

        $this->currencies->delete($currencyId);
        Response::redirect('/currencies');
    }

    /** Кодот е уредлив само при создавање — по создавање е фиксен (веќе искористен на фактури), $excludeId != null значи уредување. */
    private function validate(Request $request, ?int $excludeId): array
    {
        $errors = [];

        if ($excludeId === null) {
            $code = strtoupper(trim((string) $request->input('code')));

            if ($code === '') {
                $errors['code'] = 'Кодот е задолжителен (пр. EUR).';
            } elseif (strlen($code) !== 3) {
                $errors['code'] = 'Кодот мора да има точно 3 букви (ISO 4217, пр. EUR).';
            } elseif ($this->currencies->codeExists($code)) {
                $errors['code'] = 'Веќе постои валута со овој код.';
            }
        }

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        return $errors;
    }
}
