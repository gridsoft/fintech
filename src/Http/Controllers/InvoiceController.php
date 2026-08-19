<?php

namespace App\Http\Controllers;

use App\Core\Dates;
use App\Core\Request;
use App\Core\Response;
use App\Repository\CurrencyRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\ProductRepository;
use App\Repository\ServiceRepository;
use App\Service\Einvoice\SalesInvoiceEinvoiceService;
use App\Service\InvoiceService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class InvoiceController
{
    private const DEFAULT_DUE_DAYS = 15;

    private InvoiceRepository $invoices;
    private PartnerRepository $partners;
    private ProductRepository $products;
    private ServiceRepository $services;
    private CurrencyRepository $currencies;
    private InvoiceService $service;
    private SalesInvoiceEinvoiceService $einvoiceService;

    public function __construct()
    {
        $this->invoices = new InvoiceRepository();
        $this->partners = new PartnerRepository();
        $this->products = new ProductRepository();
        $this->services = new ServiceRepository();
        $this->currencies = new CurrencyRepository();
        $this->service = new InvoiceService($this->invoices);
        $this->einvoiceService = new SalesInvoiceEinvoiceService($this->invoices, $this->partners, $this->currencies);
    }

    public function index(Request $request): void
    {
        $invoices = $this->invoices->all();
        $partnersById = $this->partnersById();

        Response::view('invoices/index', [
            'pageTitle' => 'Фактури',
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури'],
            'invoices' => $invoices,
            'partnersById' => $partnersById,
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('invoices/form', [
            'pageTitle' => 'Нова фактура',
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури' => '/invoices', 'Нова фактура'],
            'partners' => $this->partners->all(),
            'products' => $this->products->allActive(),
            'services' => $this->services->allActive(),
            'currencies' => $this->currencies->allActive(),
            'errors' => [],
            'old' => [
                'partner_id' => '',
                'date' => date('Y-m-d'),
                'due_days' => (string) self::DEFAULT_DUE_DAYS,
                'currency_id' => (string) $this->currencies->base()->id,
                'exchange_rate' => '1.000000',
                'lines' => [
                    ['type' => '', 'item_id' => '', 'quantity' => '1', 'unit_price' => '', 'description' => ''],
                ],
            ],
        ]);
    }

    public function store(Request $request): void
    {
        $partnerId = $request->input('partner_id');
        $date = $request->input('date');
        $dueDays = $request->input('due_days', (string) self::DEFAULT_DUE_DAYS);
        $currencyId = $request->input('currency_id');
        $exchangeRate = (string) $request->input('exchange_rate', '1.000000');
        $lines = $this->collectLines($request);

        $errors = [];

        if (!$partnerId) {
            $errors['partner_id'] = 'Изберете партнер.';
        }

        if (!$date) {
            $errors['date'] = 'Датумот е задолжителен.';
        }

        if ($dueDays === '' || $dueDays === null || (int) $dueDays < 0) {
            $errors['due_days'] = 'Рокот на плаќање (денови) е задолжителен и не може да биде негативен.';
        }

        if (!$errors) {
            try {
                $dueDate = Dates::addDays($date, (int) $dueDays);
                $invoiceId = $this->service->createInvoice((int) $partnerId, $date, $dueDate, $lines, $currencyId ? (int) $currencyId : null, $exchangeRate);
                Response::redirect("/invoices/$invoiceId");
                return;
            } catch (InvalidArgumentException $e) {
                $errors['lines'] = $e->getMessage();
            }
        }

        Response::view('invoices/form', [
            'pageTitle' => 'Нова фактура',
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури' => '/invoices', 'Нова фактура'],
            'partners' => $this->partners->all(),
            'products' => $this->products->allActive(),
            'services' => $this->services->allActive(),
            'currencies' => $this->currencies->allActive(),
            'errors' => $errors,
            'old' => [
                'partner_id' => $partnerId,
                'date' => $date,
                'due_days' => $dueDays,
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'lines' => $lines ?: [
                    ['type' => '', 'item_id' => '', 'quantity' => '1', 'unit_price' => '', 'description' => ''],
                ],
            ],
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $invoice = $this->invoices->find((int) $id);

        if (!$invoice) {
            Response::html('<h1>404</h1><p>Фактурата не е пронајдена.</p>', 404);
            return;
        }

        if (!in_array($invoice->status, ['draft', 'issued'], true)) {
            Response::html('<h1>Грешка</h1><p>Само нацрт или издадена фактура може да се уредува.</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        if ($invoice->status === 'issued') {
            $blockReason = $this->service->issuedInvoiceEditBlockReason($invoice);

            if ($blockReason !== null) {
                Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($blockReason) . '</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
                return;
            }
        }

        Response::view('invoices/form', [
            'pageTitle' => "Уреди фактура {$invoice->number}",
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури' => '/invoices', $invoice->number => "/invoices/{$invoice->id}", 'Уреди'],
            'partners' => $this->partners->all(),
            'products' => $this->products->allActive(),
            'services' => $this->services->allActive(),
            'currencies' => $this->currencies->allActive(),
            'errors' => [],
            'formAction' => "/invoices/{$invoice->id}",
            'submitLabel' => 'Зачувај промени',
            'cancelUrl' => "/invoices/{$invoice->id}",
            'repostWarning' => $invoice->status === 'issued',
            'old' => [
                'partner_id' => (string) $invoice->partnerId,
                'date' => $invoice->date,
                'due_days' => (string) Dates::daysBetween($invoice->date, $invoice->dueDate),
                'currency_id' => (string) $invoice->currencyId,
                'exchange_rate' => $invoice->exchangeRate,
                'lines' => array_map(fn ($line) => [
                    'type' => $line->productId !== null ? 'product' : 'service',
                    'item_id' => (string) ($line->productId ?? $line->serviceId),
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unitPrice,
                    'description' => $line->description ?? '',
                ], $invoice->lines),
            ],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $partnerId = $request->input('partner_id');
        $date = $request->input('date');
        $dueDays = $request->input('due_days', (string) self::DEFAULT_DUE_DAYS);
        $currencyId = $request->input('currency_id');
        $exchangeRate = (string) $request->input('exchange_rate', '1.000000');
        $lines = $this->collectLines($request);

        $errors = [];

        if (!$partnerId) {
            $errors['partner_id'] = 'Изберете партнер.';
        }

        if (!$date) {
            $errors['date'] = 'Датумот е задолжителен.';
        }

        if ($dueDays === '' || $dueDays === null || (int) $dueDays < 0) {
            $errors['due_days'] = 'Рокот на плаќање (денови) е задолжителен и не може да биде негативен.';
        }

        if (!$errors) {
            try {
                $dueDate = Dates::addDays($date, (int) $dueDays);
                $this->service->updateInvoice((int) $id, (int) $partnerId, $date, $dueDate, $lines, $currencyId ? (int) $currencyId : null, $exchangeRate);
                Response::redirect("/invoices/$id");
                return;
            } catch (InvalidArgumentException $e) {
                $errors['lines'] = $e->getMessage();
            } catch (RuntimeException $e) {
                Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
                return;
            }
        }

        Response::view('invoices/form', [
            'pageTitle' => 'Уреди фактура',
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури' => '/invoices', 'Уреди'],
            'partners' => $this->partners->all(),
            'products' => $this->products->allActive(),
            'services' => $this->services->allActive(),
            'currencies' => $this->currencies->allActive(),
            'errors' => $errors,
            'formAction' => "/invoices/$id",
            'submitLabel' => 'Зачувај промени',
            'cancelUrl' => "/invoices/$id",
            'repostWarning' => ($this->invoices->find((int) $id))->status === 'issued',
            'old' => [
                'partner_id' => $partnerId,
                'date' => $date,
                'due_days' => $dueDays,
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'lines' => $lines ?: [
                    ['type' => '', 'item_id' => '', 'quantity' => '1', 'unit_price' => '', 'description' => ''],
                ],
            ],
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $invoice = $this->invoices->find((int) $id);

        if (!$invoice) {
            Response::html('<h1>404</h1><p>Фактурата не е пронајдена.</p>', 404);
            return;
        }

        $partner = $this->partners->find($invoice->partnerId);
        $productsById = $this->productsById();
        $servicesById = $this->servicesById();

        Response::view('invoices/show', [
            'pageTitle' => "Фактура {$invoice->number}",
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури' => '/invoices', $invoice->number],
            'invoice' => $invoice,
            'partner' => $partner,
            'productsById' => $productsById,
            'servicesById' => $servicesById,
            'currency' => $this->currencies->find($invoice->currencyId),
        ]);
    }

    public function issue(Request $request, string $id): void
    {
        try {
            $this->service->issue((int) $id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/invoices/$id");
    }

    public function markPaid(Request $request, string $id): void
    {
        try {
            $this->service->markPaid((int) $id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/invoices/$id");
    }

    public function cancel(Request $request, string $id): void
    {
        try {
            $this->service->cancel((int) $id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/invoices/$id");
    }

    public function sendEinvoice(Request $request, string $id): void
    {
        try {
            $this->einvoiceService->send((int) $id);
        } catch (Throwable $e) {
            Response::html('<h1>Грешка при праќање е-фактура</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/invoices/$id");
    }

    private function collectLines(Request $request): array
    {
        $items = $request->input('line_item', []);
        $quantities = $request->input('line_quantity', []);
        $unitPrices = $request->input('line_unit_price', []);
        $descriptions = $request->input('line_description', []);

        $lines = [];
        foreach ($items as $i => $item) {
            if ($item === '') {
                continue;
            }

            [$type, $itemId] = array_pad(explode(':', $item, 2), 2, null);

            $lines[] = [
                'type' => $type,
                'item_id' => $itemId,
                'quantity' => $quantities[$i] ?? '',
                'unit_price' => $unitPrices[$i] ?? '',
                'description' => $descriptions[$i] ?? '',
            ];
        }

        return $lines;
    }

    /** @return array<int, \App\Domain\Partners\Partner> */
    private function partnersById(): array
    {
        $partners = $this->partners->all();

        return array_combine(array_map(fn ($p) => $p->id, $partners), $partners);
    }

    /** @return array<int, \App\Domain\Invoicing\Product> */
    private function productsById(): array
    {
        $products = $this->products->all();

        return array_combine(array_map(fn ($p) => $p->id, $products), $products);
    }

    /** @return array<int, \App\Domain\Invoicing\Service> */
    private function servicesById(): array
    {
        $services = $this->services->all();

        return array_combine(array_map(fn ($s) => $s->id, $services), $services);
    }
}
