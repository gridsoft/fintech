<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Service\InvoiceService;
use InvalidArgumentException;
use RuntimeException;

class InvoiceController
{
    private InvoiceRepository $invoices;
    private PartnerRepository $partners;
    private InvoiceService $service;

    public function __construct()
    {
        $this->invoices = new InvoiceRepository();
        $this->partners = new PartnerRepository();
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
            'errors' => [],
            'old' => [
                'partner_id' => '',
                'date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'lines' => [
                    ['description' => '', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '18'],
                ],
            ],
        ]);
    }

    public function store(Request $request): void
    {
        $partnerId = $request->input('partner_id');
        $date = $request->input('date');
        $dueDate = $request->input('due_date');
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
                $invoiceId = $this->service->createInvoice((int) $partnerId, $date, $dueDate, $lines);
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
            'errors' => $errors,
            'old' => [
                'partner_id' => $partnerId,
                'date' => $date,
                'due_date' => $dueDate,
                'lines' => $lines ?: [
                    ['description' => '', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '18'],
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

        Response::view('invoices/show', [
            'pageTitle' => "Фактура {$invoice->number}",
            'activeNav' => 'invoices',
            'breadcrumb' => ['Почетна' => '/', 'Фактури' => '/invoices', $invoice->number],
            'invoice' => $invoice,
            'partner' => $partner,
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
        $descriptions = $request->input('line_description', []);
        $quantities = $request->input('line_quantity', []);
        $unitPrices = $request->input('line_unit_price', []);
        $vatRates = $request->input('line_vat_rate', []);

        $lines = [];
        foreach ($descriptions as $i => $description) {
            if (trim((string) $description) === '') {
                continue;
            }

            $lines[] = [
                'description' => $description,
                'quantity' => $quantities[$i] ?? '',
                'unit_price' => $unitPrices[$i] ?? '',
                'vat_rate' => $vatRates[$i] ?? '',
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
}
