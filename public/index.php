<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Request;

$config = require __DIR__ . '/../config/config.php';

if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$router = require __DIR__ . '/../src/routes.php';
$request = new Request();

$router->dispatch($request);
