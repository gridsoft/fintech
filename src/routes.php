<?php

use App\Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

$router->get('/accounts', [AccountController::class, 'index']);
$router->get('/accounts/create', [AccountController::class, 'create']);
$router->post('/accounts', [AccountController::class, 'store']);
$router->get('/accounts/{id}/edit', [AccountController::class, 'edit']);
$router->get('/accounts/{id}/ledger', [AccountController::class, 'ledger']);
$router->post('/accounts/{id}', [AccountController::class, 'update']);
$router->post('/accounts/{id}/delete', [AccountController::class, 'destroy']);

$router->get('/journal', [JournalController::class, 'index']);
$router->get('/journal/create', [JournalController::class, 'create']);
$router->post('/journal', [JournalController::class, 'store']);
$router->get('/journal/{id}', [JournalController::class, 'show']);

$router->get('/partners', [PartnerController::class, 'index']);
$router->get('/partners/create', [PartnerController::class, 'create']);
$router->post('/partners', [PartnerController::class, 'store']);
$router->get('/partners/{id}/edit', [PartnerController::class, 'edit']);
$router->post('/partners/{id}', [PartnerController::class, 'update']);
$router->post('/partners/{id}/delete', [PartnerController::class, 'destroy']);
$router->get('/partners/{id}/statement', [PartnerController::class, 'statement']);

$router->get('/invoices', [InvoiceController::class, 'index']);
$router->get('/invoices/create', [InvoiceController::class, 'create']);
$router->post('/invoices', [InvoiceController::class, 'store']);
$router->get('/invoices/{id}', [InvoiceController::class, 'show']);
$router->post('/invoices/{id}/issue', [InvoiceController::class, 'issue']);
$router->post('/invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid']);
$router->post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel']);

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/trial-balance', [ReportController::class, 'trialBalance']);
$router->get('/reports/vat', [ReportController::class, 'vat']);
$router->get('/reports/open-items', [ReportController::class, 'openItems']);

return $router;
