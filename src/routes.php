<?php

use App\Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

$router->get('/accounts', [AccountController::class, 'index']);
$router->get('/accounts/create', [AccountController::class, 'create']);
$router->post('/accounts', [AccountController::class, 'store']);
$router->get('/accounts/{id}/edit', [AccountController::class, 'edit']);
$router->post('/accounts/{id}', [AccountController::class, 'update']);
$router->post('/accounts/{id}/delete', [AccountController::class, 'destroy']);

return $router;
