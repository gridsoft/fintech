<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Accounting\Nalog;
use App\Repository\NalogRepository;
use App\Repository\TerkRepository;

class NalogController
{
    private NalogRepository $nalozi;
    private TerkRepository $terkovi;

    public function __construct()
    {
        $this->nalozi = new NalogRepository();
        $this->terkovi = new TerkRepository();
    }

    public function index(Request $request): void
    {
        $terkoviById = $this->terkoviById();

        Response::view('nalozi/index', [
            'pageTitle' => 'Налози',
            'activeNav' => 'nalozi',
            'breadcrumb' => ['Почетна' => '/', 'Налози'],
            'nalozi' => $this->nalozi->all(),
            'terkoviById' => $terkoviById,
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('nalozi/form', [
            'pageTitle' => 'Нов налог',
            'activeNav' => 'nalozi',
            'breadcrumb' => ['Почетна' => '/', 'Налози' => '/nalozi', 'Нов налог'],
            'nalog' => null,
            'terkovi' => $this->terkovi->all(),
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            Response::view('nalozi/form', [
                'pageTitle' => 'Нов налог',
                'activeNav' => 'nalozi',
                'breadcrumb' => ['Почетна' => '/', 'Налози' => '/nalozi', 'Нов налог'],
                'nalog' => null,
                'terkovi' => $this->terkovi->all(),
                'errors' => $errors,
            ]);
            return;
        }

        $nalog = new Nalog(
            trim($request->input('name')),
            (int) $request->input('terk_id'),
            $request->input('is_active') === '1'
        );

        $this->nalozi->create($nalog);

        Response::redirect('/nalozi');
    }

    public function edit(Request $request, string $id): void
    {
        $nalog = $this->nalozi->find((int) $id);

        if (!$nalog) {
            Response::html('<h1>404</h1><p>Налогот не е пронајден.</p>', 404);
            return;
        }

        Response::view('nalozi/form', [
            'pageTitle' => 'Уреди налог',
            'activeNav' => 'nalozi',
            'breadcrumb' => ['Почетна' => '/', 'Налози' => '/nalozi', 'Уреди налог'],
            'nalog' => $nalog,
            'terkovi' => $this->terkovi->all(),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $nalog = $this->nalozi->find((int) $id);

        if (!$nalog) {
            Response::html('<h1>404</h1><p>Налогот не е пронајден.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            Response::view('nalozi/form', [
                'pageTitle' => 'Уреди налог',
                'activeNav' => 'nalozi',
                'breadcrumb' => ['Почетна' => '/', 'Налози' => '/nalozi', 'Уреди налог'],
                'nalog' => $nalog,
                'terkovi' => $this->terkovi->all(),
                'errors' => $errors,
            ]);
            return;
        }

        $nalog->name = trim($request->input('name'));
        $nalog->terkId = (int) $request->input('terk_id');
        $nalog->isActive = $request->input('is_active') === '1';

        $this->nalozi->update($nalog);

        Response::redirect('/nalozi');
    }

    public function destroy(Request $request, string $id): void
    {
        $nalogId = (int) $id;

        if ($this->nalozi->isUsedByInvoice($nalogId)) {
            Response::html('<h1>Грешка</h1><p>Овој налог се користи од барем една фактура и не може да се избрише. Деактивирај го наместо тоа.</p><p><a href="/nalozi">Назад</a></p>', 422);
            return;
        }

        $this->nalozi->delete($nalogId);
        Response::redirect('/nalozi');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        if (!$request->input('terk_id')) {
            $errors['terk_id'] = 'Изберете терк.';
        }

        return $errors;
    }

    /** @return array<int, \App\Domain\Accounting\Terk> */
    private function terkoviById(): array
    {
        $terkovi = $this->terkovi->all();

        return array_combine(array_map(fn ($t) => $t->id, $terkovi), $terkovi);
    }
}
