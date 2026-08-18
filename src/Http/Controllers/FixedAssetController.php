<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AccountRepository;
use App\Repository\FixedAssetRepository;
use App\Service\FixedAssetService;
use InvalidArgumentException;

class FixedAssetController
{
    private FixedAssetRepository $assets;
    private AccountRepository $accounts;
    private FixedAssetService $service;

    public function __construct()
    {
        $this->assets = new FixedAssetRepository();
        $this->accounts = new AccountRepository();
        $this->service = new FixedAssetService($this->assets, $this->accounts);
    }

    public function index(Request $request): void
    {
        $assets = $this->assets->all();
        $accumulatedByAsset = [];

        foreach ($assets as $asset) {
            $accumulatedByAsset[$asset->id] = $this->assets->accumulatedDepreciation($asset->id);
        }

        Response::view('fixed-assets/index', [
            'pageTitle' => 'Основни средства',
            'activeNav' => 'fixed-assets',
            'breadcrumb' => ['Почетна' => '/', 'Основни средства'],
            'assets' => $assets,
            'accountsById' => $this->accountsById(),
            'accumulatedByAsset' => $accumulatedByAsset,
            'defaultPeriod' => date('Y-m'),
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $asset = $this->assets->find((int) $id);

        if (!$asset) {
            Response::html('<h1>404</h1><p>Основното средство не е пронајдено.</p>', 404);
            return;
        }

        Response::view('fixed-assets/show', [
            'pageTitle' => $asset->name,
            'activeNav' => 'fixed-assets',
            'breadcrumb' => ['Почетна' => '/', 'Основни средства' => '/fixed-assets', $asset->name],
            'asset' => $asset,
            'account' => $this->accounts->find($asset->accountId),
            'accumulated' => $this->assets->accumulatedDepreciation($asset->id),
            'entries' => $this->assets->depreciationEntriesForAsset($asset->id),
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $asset = $this->assets->find((int) $id);

        if (!$asset) {
            Response::html('<h1>404</h1><p>Основното средство не е пронајдено.</p>', 404);
            return;
        }

        Response::view('fixed-assets/edit', [
            'pageTitle' => 'Уреди — ' . $asset->name,
            'activeNav' => 'fixed-assets',
            'breadcrumb' => ['Почетна' => '/', 'Основни средства' => '/fixed-assets', $asset->name => '/fixed-assets/' . $asset->id, 'Уреди'],
            'asset' => $asset,
            'errors' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $asset = $this->assets->find((int) $id);

        if (!$asset) {
            Response::html('<h1>404</h1><p>Основното средство не е пронајдено.</p>', 404);
            return;
        }

        $name = trim((string) $request->input('name'));
        $annualRate = $request->input('annual_rate');
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Името е задолжително.';
        }

        if (!$annualRate || (float) $annualRate <= 0) {
            $errors['annual_rate'] = 'Внесете важечка стапка на амортизација (%).';
        }

        if ($errors) {
            Response::view('fixed-assets/edit', [
                'pageTitle' => 'Уреди — ' . $asset->name,
                'activeNav' => 'fixed-assets',
                'breadcrumb' => ['Почетна' => '/', 'Основни средства' => '/fixed-assets', $asset->name => '/fixed-assets/' . $asset->id, 'Уреди'],
                'asset' => $asset,
                'errors' => $errors,
            ]);
            return;
        }

        $asset->name = $name;
        $asset->annualRate = number_format((float) $annualRate, 2, '.', '');
        $this->assets->update($asset);

        Response::redirect('/fixed-assets/' . $asset->id);
    }

    public function runDepreciation(Request $request): void
    {
        $period = (string) $request->input('period');

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            Response::html('<h1>Грешка</h1><p>Изберете важечки период (месец).</p><p><a href="/fixed-assets">Назад</a></p>', 422);
            return;
        }

        $periodDate = date('Y-m-t', strtotime($period . '-01'));

        try {
            $this->service->runDepreciation($periodDate);
        } catch (InvalidArgumentException $e) {
            Response::html('<h1>Грешка</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="/fixed-assets">Назад</a></p>', 422);
            return;
        }

        Response::redirect('/fixed-assets');
    }

    /** @return array<int, \App\Domain\Accounting\Account> */
    private function accountsById(): array
    {
        $accounts = $this->accounts->all();

        return array_combine(array_map(fn ($a) => $a->id, $accounts), $accounts);
    }
}
