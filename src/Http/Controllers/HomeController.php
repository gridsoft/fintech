<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Service\DashboardService;

class HomeController
{
    public function index(Request $request): void
    {
        $summary = (new DashboardService())->summary();

        Response::view('home/index', array_merge([
            'pageTitle' => 'Контролна табла',
            'activeNav' => 'home',
        ], $summary));
    }
}
