<?php

use App\Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VatRateController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\BankStatementController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\AdvanceController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\FxRevaluationController;

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
$router->post('/partners/{id}/employees', [PartnerController::class, 'addEmployee']);
$router->post('/partners/{id}/employees/{employeeId}/delete', [PartnerController::class, 'deleteEmployee']);
$router->post('/partners/{id}/custom-fields', [PartnerController::class, 'addCustomField']);
$router->post('/partners/{id}/custom-fields/{fieldId}/delete', [PartnerController::class, 'deleteCustomField']);

$router->get('/invoices', [InvoiceController::class, 'index']);
$router->get('/invoices/create', [InvoiceController::class, 'create']);
$router->post('/invoices', [InvoiceController::class, 'store']);
$router->get('/invoices/{id}', [InvoiceController::class, 'show']);
$router->post('/invoices/{id}/issue', [InvoiceController::class, 'issue']);
$router->post('/invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid']);
$router->post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel']);
$router->post('/invoices/{id}/send-einvoice', [InvoiceController::class, 'sendEinvoice']);

$router->get('/purchase-invoices', [PurchaseInvoiceController::class, 'index']);
$router->get('/purchase-invoices/create', [PurchaseInvoiceController::class, 'create']);
$router->post('/purchase-invoices', [PurchaseInvoiceController::class, 'store']);
$router->get('/purchase-invoices/{id}', [PurchaseInvoiceController::class, 'show']);
$router->post('/purchase-invoices/{id}/post', [PurchaseInvoiceController::class, 'post']);
$router->post('/purchase-invoices/{id}/mark-paid', [PurchaseInvoiceController::class, 'markPaid']);
$router->post('/purchase-invoices/{id}/cancel', [PurchaseInvoiceController::class, 'cancel']);

$router->get('/expense-categories', [ExpenseCategoryController::class, 'index']);
$router->get('/expense-categories/create', [ExpenseCategoryController::class, 'create']);
$router->post('/expense-categories', [ExpenseCategoryController::class, 'store']);
$router->get('/expense-categories/{id}/edit', [ExpenseCategoryController::class, 'edit']);
$router->post('/expense-categories/{id}', [ExpenseCategoryController::class, 'update']);
$router->post('/expense-categories/{id}/delete', [ExpenseCategoryController::class, 'destroy']);

$router->get('/bank-statements', [BankStatementController::class, 'index']);
$router->get('/bank-statements/create', [BankStatementController::class, 'create']);
$router->post('/bank-statements', [BankStatementController::class, 'store']);
$router->get('/bank-statements/{id}', [BankStatementController::class, 'show']);
$router->post('/bank-statements/{id}/transactions', [BankStatementController::class, 'addTransactions']);
$router->post('/bank-transactions/{id}/match', [BankStatementController::class, 'match']);

$router->get('/fixed-assets', [FixedAssetController::class, 'index']);
$router->post('/fixed-assets/run-depreciation', [FixedAssetController::class, 'runDepreciation']);
$router->get('/fixed-assets/{id}', [FixedAssetController::class, 'show']);
$router->get('/fixed-assets/{id}/edit', [FixedAssetController::class, 'edit']);
$router->post('/fixed-assets/{id}', [FixedAssetController::class, 'update']);

$router->get('/advances', [AdvanceController::class, 'index']);
$router->post('/advances/{id}/apply', [AdvanceController::class, 'apply']);

$router->get('/currencies', [CurrencyController::class, 'index']);
$router->get('/currencies/create', [CurrencyController::class, 'create']);
$router->post('/currencies', [CurrencyController::class, 'store']);
$router->get('/currencies/{id}/edit', [CurrencyController::class, 'edit']);
$router->post('/currencies/{id}', [CurrencyController::class, 'update']);
$router->post('/currencies/{id}/delete', [CurrencyController::class, 'destroy']);

$router->get('/fx-revaluations', [FxRevaluationController::class, 'index']);
$router->post('/fx-revaluations', [FxRevaluationController::class, 'store']);

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/trial-balance', [ReportController::class, 'trialBalance']);
$router->get('/reports/vat', [ReportController::class, 'vat']);
$router->get('/reports/open-items', [ReportController::class, 'openItems']);

$router->get('/vat-rates', [VatRateController::class, 'index']);
$router->get('/vat-rates/create', [VatRateController::class, 'create']);
$router->post('/vat-rates', [VatRateController::class, 'store']);
$router->get('/vat-rates/{id}/edit', [VatRateController::class, 'edit']);
$router->post('/vat-rates/{id}', [VatRateController::class, 'update']);
$router->post('/vat-rates/{id}/delete', [VatRateController::class, 'destroy']);

$router->get('/product-categories', [ProductCategoryController::class, 'index']);
$router->get('/product-categories/create', [ProductCategoryController::class, 'create']);
$router->post('/product-categories', [ProductCategoryController::class, 'store']);
$router->get('/product-categories/{id}/edit', [ProductCategoryController::class, 'edit']);
$router->post('/product-categories/{id}', [ProductCategoryController::class, 'update']);
$router->post('/product-categories/{id}/delete', [ProductCategoryController::class, 'destroy']);

$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/create', [ProductController::class, 'create']);
$router->post('/products', [ProductController::class, 'store']);
$router->get('/products/{id}/edit', [ProductController::class, 'edit']);
$router->post('/products/{id}', [ProductController::class, 'update']);
$router->post('/products/{id}/delete', [ProductController::class, 'destroy']);

$router->get('/service-categories', [ServiceCategoryController::class, 'index']);
$router->get('/service-categories/create', [ServiceCategoryController::class, 'create']);
$router->post('/service-categories', [ServiceCategoryController::class, 'store']);
$router->get('/service-categories/{id}/edit', [ServiceCategoryController::class, 'edit']);
$router->post('/service-categories/{id}', [ServiceCategoryController::class, 'update']);
$router->post('/service-categories/{id}/delete', [ServiceCategoryController::class, 'destroy']);

$router->get('/services', [ServiceController::class, 'index']);
$router->get('/services/create', [ServiceController::class, 'create']);
$router->post('/services', [ServiceController::class, 'store']);
$router->get('/services/{id}/edit', [ServiceController::class, 'edit']);
$router->post('/services/{id}', [ServiceController::class, 'update']);
$router->post('/services/{id}/delete', [ServiceController::class, 'destroy']);

return $router;
