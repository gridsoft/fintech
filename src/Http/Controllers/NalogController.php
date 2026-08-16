<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Accounting\Nalog;
use App\Repository\NalogRepository;

class NalogController
{
    private NalogRepository $nalozi;

    public function __construct()
    {
        $this->nalozi = new NalogRepository();
    }

    public function index(Request $request): void
    {
        Response::view('nalozi/index', [
            'pageTitle' => 'Налози',
            'activeNav' => 'nalozi',
            'breadcrumb' => ['Почетна' => '/', 'Налози'],
            'nalozi' => $this->nalozi->all(),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('nalozi/form', [
            'pageTitle' => 'Нов налог',
            'activeNav' => 'nalozi',
            'breadcrumb' => ['Почетна' => '/', 'Налози' => '/nalozi', 'Нов налог'],
            'nalog' => null,
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
                'errors' => $errors,
            ]);
            return;
        }

        $nalog = new Nalog(trim($request->input('name')), $request->input('is_active') === '1');

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
                'errors' => $errors,
            ]);
            return;
        }

        $nalog->name = trim($request->input('name'));
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

        return $errors;
    }
}
