<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AccountRepository;

class HomeController
{
    public function index(Request $request): void
    {
        Response::view('home/index', [
            'pageTitle' => 'Контролна табла',
            'activeNav' => 'home',
            'accountCount' => (new AccountRepository())->count(),
        ]);
    }
}
