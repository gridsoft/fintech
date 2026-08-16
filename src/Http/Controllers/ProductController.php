<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Invoicing\Product;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;

class ProductController
{
    private ProductRepository $products;
    private ProductCategoryRepository $categories;

    public function __construct()
    {
        $this->products = new ProductRepository();
        $this->categories = new ProductCategoryRepository();
    }

    public function index(Request $request): void
    {
        $categories = $this->categories->all();

        Response::view('products/index', [
            'pageTitle' => 'Производи',
            'activeNav' => 'products',
            'breadcrumb' => ['Почетна' => '/', 'Производи'],
            'products' => $this->products->all(),
            'categoriesById' => array_combine(array_map(fn ($c) => $c->id, $categories), $categories),
        ]);
    }

    public function create(Request $request): void
    {
        Response::view('products/form', [
            'pageTitle' => 'Нов производ',
            'activeNav' => 'products',
            'breadcrumb' => ['Почетна' => '/', 'Производи' => '/products', 'Нов производ'],
            'product' => null,
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

        $product = new Product(
            trim($request->input('name')),
            (int) $request->input('category_id'),
            number_format((float) $request->input('price'), 2, '.', ''),
            $request->input('is_active') === '1'
        );

        $this->products->create($product);

        Response::redirect('/products');
    }

    public function edit(Request $request, string $id): void
    {
        $product = $this->products->find((int) $id);

        if (!$product) {
            Response::html('<h1>404</h1><p>Производот не е пронајден.</p>', 404);
            return;
        }

        Response::view('products/form', [
            'pageTitle' => 'Уреди производ',
            'activeNav' => 'products',
            'breadcrumb' => ['Почетна' => '/', 'Производи' => '/products', 'Уреди производ'],
            'product' => $product,
            'categories' => $this->categories->allActive(),
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $product = $this->products->find((int) $id);

        if (!$product) {
            Response::html('<h1>404</h1><p>Производот не е пронајден.</p>', 404);
            return;
        }

        $errors = $this->validate($request);

        if ($errors) {
            $this->renderForm($product, $errors);
            return;
        }

        $product->name = trim($request->input('name'));
        $product->categoryId = (int) $request->input('category_id');
        $product->price = number_format((float) $request->input('price'), 2, '.', '');
        $product->isActive = $request->input('is_active') === '1';

        $this->products->update($product);

        Response::redirect('/products');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->products->delete((int) $id);
        Response::redirect('/products');
    }

    private function renderForm(?Product $product, array $errors): void
    {
        Response::view('products/form', [
            'pageTitle' => $product ? 'Уреди производ' : 'Нов производ',
            'activeNav' => 'products',
            'breadcrumb' => ['Почетна' => '/', 'Производи' => '/products', $product ? 'Уреди производ' : 'Нов производ'],
            'product' => $product,
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
