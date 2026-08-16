<?php

use App\Core\Router;
use App\Http\Controllers\HomeController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

return $router;
