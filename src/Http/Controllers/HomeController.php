<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;

class HomeController
{
    public function index(Request $request): void
    {
        Response::view('home/index');
    }
}
