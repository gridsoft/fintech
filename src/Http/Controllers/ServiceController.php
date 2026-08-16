<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Invoicing\Service;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;

class ServiceController
{
    private ServiceRepository $services;
    private ServiceCategoryRepository $categories;

    public function __construct()
    {
        $this->services = new ServiceRepository();
        $this->categories = new ServiceCategoryRepository();
    }

    public function index(Request $request): void
    {
        $categories = $this->categories->all();

        Response::view('services/index', [
            'pageTitle' => 'Услуги',
            'activeNav' => 'services',
            'breadcrumb' => ['Почетна' => '/', 'Услуги'],
            'services' => $this->services->all(),
            'categoriesById' => array_combine(array_map(fn ($c) => $c->id, $categories), $categories),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('services/form', [
            'pageTitle' => 'Нова услуга',
            'activeNav' => 'services',
            'breadcrumb' => ['Почетна' => '/', 'Услуги' => '/services', 'Нова услуга'],
            'service' => null,
            'categories' => $this->categories->allActive(),
            'errors' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm(null, $errors);
            return;
        }

        $service = new Service(
            trim($request->input('name')),
            (int) $request->input('category_id'),
            number_format((float) $request->input('price'), 2, '.', ''),
            $request->input('is_active') === '1'
        );

        $this->services->create($service);

        Response::redirect('/services');
    }

    public function edit(Request $request, string $id): void
    {
        $service = $this->services->find((int) $id);

        if (!$service) {
            Response::html('<h1>404</h1><p>Услугата не е пронајдена.</p>', 404);
            return;
        }

        Response::view('services/form', [
            'pageTitle' => 'Уреди услуга',
            'activeNav' => 'services',
            'breadcrumb' => ['Почетна' => '/', 'Услуги' => '/services', 'Уреди услуга'],
            'service' => $service,
            'categories' => $this->categories->allActive(),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $service = $this->services->find((int) $id);

        if (!$service) {
            Response::html('<h1>404</h1><p>Услугата не е пронајдена.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm($service, $errors);
            return;
        }

        $service->name = trim($request->input('name'));
        $service->categoryId = (int) $request->input('category_id');
        $service->price = number_format((float) $request->input('price'), 2, '.', '');
        $service->isActive = $request->input('is_active') === '1';

        $this->services->update($service);

        Response::redirect('/services');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->services->delete((int) $id);
        Response::redirect('/services');
    }

    private function renderForm(?Service $service, array $errors): void
    {
        Response::view('services/form', [
            'pageTitle' => $service ? 'Уреди услуга' : 'Нова услуга',
            'activeNav' => 'services',
            'breadcrumb' => ['Почетна' => '/', 'Услуги' => '/services', $service ? 'Уреди услуга' : 'Нова услуга'],
            'service' => $service,
            'categories' => $this->categories->allActive(),
            'errors' => $errors,
        ]);
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (trim((string) $request->input('name')) === '') {
            $errors['name'] = 'Називот е задолжителен.';
        }

        if (!$request->input('category_id')) {
            $errors['category_id'] = 'Изберете категорија.';
        }

        if ((float) $request->input('price') < 0) {
            $errors['price'] = 'Цената не може да биде негативна.';
        }

        return $errors;
    }
}
