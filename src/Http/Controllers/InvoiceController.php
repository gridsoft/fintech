<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\CurrencyRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\ProductRepository;
use App\Repository\ServiceRepository;
use App\Service\InvoiceService;
use InvalidArgumentException;
use RuntimeException;

class InvoiceController
{
    private InvoiceRepository $invoices;
    private PartnerRepository $partners;
    private ProductRepository $products;
    private ServiceRepository $services;
    private CurrencyRepository $currencies;
    private InvoiceService $service;

    public function __construct()
    {
        $this->invoices = new InvoiceRepository();
        $this->partners = new PartnerRepository();
        $this->products = new ProductRepository();
        $this->services = new ServiceRepository();
        $this->currencies = new CurrencyRepository();
        $this->service = new InvoiceService($this->invoices);
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
                'due_date' => date('Y-m-d', strtotime('+30 days')),
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
        $dueDate = $request->input('due_date');
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

        if (!$dueDate) {
            $errors['due_date'] = 'Рокот на плаќање е задолжителен.';
        }

        if (!$errors) {
            try {
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
                'due_date' => $dueDate,
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
