<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\CurrencyRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\PartnerRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\PurchaseInvoiceService;
use InvalidArgumentException;
use RuntimeException;

class PurchaseInvoiceController
{
    private PurchaseInvoiceRepository $invoices;
    private PartnerRepository $partners;
    private ExpenseCategoryRepository $expenseCategories;
    private VatRateRepository $vatRates;
    private CurrencyRepository $currencies;
    private PurchaseInvoiceService $service;

    public function __construct()
    {
        $this->invoices = new PurchaseInvoiceRepository();
        $this->partners = new PartnerRepository();
        $this->expenseCategories = new ExpenseCategoryRepository();
        $this->vatRates = new VatRateRepository();
        $this->currencies = new CurrencyRepository();
        $this->service = new PurchaseInvoiceService($this->invoices);
    }

    public function index(Request $request): void
    {
        Response::view('purchase-invoices/index', [
            'pageTitle' => 'Влезни фактури',
            'activeNav' => 'purchase-invoices',
            'breadcrumb' => ['Почетна' => '/', 'Влезни фактури'],
            'invoices' => $this->invoices->all(),
            'partnersById' => $this->partnersById(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('purchase-invoices/form', [
            'pageTitle' => 'Нова влезна фактура',
            'activeNav' => 'purchase-invoices',
            'breadcrumb' => ['Почетна' => '/', 'Влезни фактури' => '/purchase-invoices', 'Нова влезна фактура'],
            'partners' => $this->partners->all(),
            'expenseCategories' => $this->expenseCategories->allActive(),
            'vatRates' => $this->vatRates->allActive(),
            'currencies' => $this->currencies->allActive(),
            'errors' => [],
            'old' => [
                'partner_id' => '',
                'supplier_number' => '',
                'date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'currency_id' => (string) $this->currencies->base()->id,
                'exchange_rate' => '1.000000',
                'lines' => [
                    ['category_id' => '', 'vat_rate_id' => '', 'quantity' => '1', 'unit_price' => '', 'description' => ''],
                ],
            ],
        ]);
    }

    public function store(Request $request): void
    {
        $partnerId = $request->input('partner_id');
        $supplierNumber = $request->input('supplier_number');
        $date = $request->input('date');
        $dueDate = $request->input('due_date');
        $currencyId = $request->input('currency_id');
        $exchangeRate = (string) $request->input('exchange_rate', '1.000000');
        $lines = $this->collectLines($request);

        $errors = [];

        if (!$partnerId) {
            $errors['partner_id'] = 'Изберете партнер.';
        }

        if (trim((string) $supplierNumber) === '') {
            $errors['supplier_number'] = 'Бројот на фактурата од добавувачот е задолжителен.';
        }

        if (!$date) {
            $errors['date'] = 'Датумот е задолжителен.';
        }

        if (!$dueDate) {
            $errors['due_date'] = 'Рокот на плаќање е задолжителен.';
        }

        if (!$errors) {
            try {
                $invoiceId = $this->service->createPurchaseInvoice((int) $partnerId, $supplierNumber, $date, $dueDate, $lines, $currencyId ? (int) $currencyId : null, $exchangeRate);
                Response::redirect("/purchase-invoices/$invoiceId");
                return;
            } catch (InvalidArgumentException|RuntimeException $e) {
                $errors['lines'] = $e->getMessage();
            }
        }

        Response::view('purchase-invoices/form', [
            'pageTitle' => 'Нова влезна фактура',
            'activeNav' => 'purchase-invoices',
            'breadcrumb' => ['Почетна' => '/', 'Влезни фактури' => '/purchase-invoices', 'Нова влезна фактура'],
            'partners' => $this->partners->all(),
            'expenseCategories' => $this->expenseCategories->allActive(),
            'vatRates' => $this->vatRates->allActive(),
            'currencies' => $this->currencies->allActive(),
            'errors' => $errors,
            'old' => [
                'partner_id' => $partnerId,
                'supplier_number' => $supplierNumber,
                'date' => $date,
                'due_date' => $dueDate,
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'lines' => $lines ?: [
                    ['category_id' => '', 'vat_rate_id' => '', 'quantity' => '1', 'unit_price' => '', 'description' => ''],
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

        Response::view('purchase-invoices/show', [
            'pageTitle' => "Влезна фактура {$invoice->supplierNumber}",
            'activeNav' => 'purchase-invoices',
            'breadcrumb' => ['Почетна' => '/', 'Влезни фактури' => '/purchase-invoices', $invoice->supplierNumber],
            'invoice' => $invoice,
            'partner' => $this->partners->find($invoice->partnerId),
            'categoriesById' => $this->expenseCategoriesById(),
            'currency' => $this->currencies->find($invoice->currencyId),
        ]);
    }

    public function post(Request $request, string $id): void
    {
        try {
            $this->service->post((int) $id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/purchase-invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/purchase-invoices/$id");
    }

    public function markPaid(Request $request, string $id): void
    {
        try {
            $this->service->markPaid((int) $id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/purchase-invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/purchase-invoices/$id");
    }

    public function cancel(Request $request, string $id): void
    {
        try {
            $this->service->cancel((int) $id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/purchase-invoices/' . (int) $id . '">Назад</a></p>', 422);
            return;
        }

        Response::redirect("/purchase-invoices/$id");
    }

    private function collectLines(Request $request): array
    {
        $categoryIds = $request->input('line_category_id', []);
        $vatRateIds = $request->input('line_vat_rate_id', []);
        $quantities = $request->input('line_quantity', []);
        $unitPrices = $request->input('line_unit_price', []);
        $descriptions = $request->input('line_description', []);

        $lines = [];
        foreach ($categoryIds as $i => $categoryId) {
            if ($categoryId === '') {
                continue;
            }

            $lines[] = [
                'category_id' => $categoryId,
                'vat_rate_id' => $vatRateIds[$i] ?? '',
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

    /** @return array<int, \App\Domain\Invoicing\ExpenseCategory> */
    private function expenseCategoriesById(): array
    {
        $categories = $this->expenseCategories->all();

        return array_combine(array_map(fn ($c) => $c->id, $categories), $categories);
    }
}
