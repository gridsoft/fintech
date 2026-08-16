<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Service\ReportService;

class ReportController
{
    private ReportService $reports;

    public function __construct()
    {
        $this->reports = new ReportService();
    }

    public function index(Request $request): void
    {
        Response::view('reports/index', [
            'pageTitle' => 'Извештаи',
            'activeNav' => 'reports',
            'breadcrumb' => ['Почетна' => '/', 'Извештаи'],
        ]);
    }

    public function trialBalance(Request $request): void
    {
        Response::view('reports/trial-balance', [
            'pageTitle' => 'Бруто биланс',
            'activeNav' => 'reports',
            'breadcrumb' => ['Почетна' => '/', 'Извештаи' => '/reports', 'Бруто биланс'],
            'report' => $this->reports->trialBalance(),
        ]);
    }

    public function vat(Request $request): void
    {
        $from = $request->input('from') ?: date('Y-m-01');
        $to = $request->input('to') ?: date('Y-m-t');

        Response::view('reports/vat', [
            'pageTitle' => 'ДДВ евиденција',
            'activeNav' => 'reports',
            'breadcrumb' => ['Почетна' => '/', 'Извештаи' => '/reports', 'ДДВ евиденција'],
            'report' => $this->reports->vatSummary($from, $to),
        ]);
    }

    public function openItems(Request $request): void
    {
        Response::view('reports/open-items', [
            'pageTitle' => 'Отворени ставки по партнер',
            'activeNav' => 'reports',
            'breadcrumb' => ['Почетна' => '/', 'Извештаи' => '/reports', 'Отворени ставки'],
            'rows' => $this->reports->openItemsByPartner(),
        ]);
    }
}
