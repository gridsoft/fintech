<?php

/**
 * Dummy тест-податоци: партнери, ДДВ стапки, категории + производи/услуги,
 * неколку фактури во различни статуси, и рачни journal записи. Минува низ
 * вистинските сервиси (InvoiceService/LedgerService) за да сè биде реално
 * и балансирано, со автоматска резолуција сметка/ДДВ (POSTING_RULES_ADDENDUM.md).
 *
 * Безбедно за повторно извршување — ако партнерот-маркер веќе постои, излегува без промени.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\ExpenseCategory;
use App\Domain\Invoicing\ProductCategory;
use App\Domain\Invoicing\Product;
use App\Domain\Invoicing\ServiceCategory;
use App\Domain\Invoicing\Service;
use App\Domain\Partners\Partner;
use App\Repository\AccountRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\PartnerRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\VatRateRepository;
use App\Service\InvoiceService;
use App\Service\LedgerService;
use App\Service\PurchaseInvoiceService;

$pdo = Database::connection();
$partnerRepo = new PartnerRepository();
$accountRepo = new AccountRepository();
$vatRateRepo = new VatRateRepository();
$productCategoryRepo = new ProductCategoryRepository();
$productRepo = new ProductRepository();
$serviceCategoryRepo = new ServiceCategoryRepository();
$serviceRepo = new ServiceRepository();
$expenseCategoryRepo = new ExpenseCategoryRepository();
$purchaseInvoiceRepo = new PurchaseInvoiceRepository();
$invoiceService = new InvoiceService();
$purchaseInvoiceService = new PurchaseInvoiceService($purchaseInvoiceRepo);
$ledgerService = new LedgerService();

$markerName = 'Алфа Трговија ДООЕЛ';
foreach ($partnerRepo->all() as $existing) {
    if ($existing->name === $markerName) {
        echo "Dummy податоците веќе постојат (пронајден '$markerName') — прескокнувам.\n";
        exit(0);
    }
}

// --- ДДВ стапки -----------------------------------------------------------

$vatPayable = $accountRepo->findByCode('260'); // Обврски за данокот на додадена вредност
$vatReceivable = $accountRepo->findByCode('160'); // Данок на додадена вредност (влезен, одбивен)

$vatStandard = $vatRateRepo->create(new VatRate('Стандардна 18%', '18.00', 'standard', $vatPayable->id, $vatReceivable->id));
$vatReduced = $vatRateRepo->create(new VatRate('Намалена 5%', '5.00', 'reduced', $vatPayable->id, $vatReceivable->id));
$vatZero = $vatRateRepo->create(new VatRate('Извоз 0%', '0.00', 'zero', null, null));
echo "Создадени ДДВ стапки: 18%, 5%, 0% (извоз)\n";

// --- Категории + производи/услуги ------------------------------------------

$revenueGoodsDomestic = $accountRepo->findByCode('751'); // Приходи од продажба... на домашен пазар
$revenueGoodsForeign = $accountRepo->findByCode('752'); // ...на странски пазар
$revenueServicesDomestic = $accountRepo->findByCode('751');

$goodsCategoryId = $productCategoryRepo->create(new ProductCategory(
    'Стоки — трговија',
    $revenueGoodsDomestic->id,
    $vatStandard,
    $revenueGoodsForeign->id,
    $vatZero
));
echo "Создадена категорија на производи: Стоки — трговија\n";

$servicesCategoryId = $serviceCategoryRepo->create(new ServiceCategory(
    'Консултантски и ИТ услуги',
    $revenueServicesDomestic->id,
    $vatReduced,
    $revenueGoodsForeign->id,
    $vatZero
));
echo "Создадена категорија на услуги: Консултантски и ИТ услуги\n";

$productIds = [
    'materijali' => $productRepo->create(new Product('Канцелариски материјали (пакет)', $goodsCategoryId, '450.00')),
    'toner' => $productRepo->create(new Product('Тонер за печатач', $goodsCategoryId, '1200.00')),
    'gradezen' => $productRepo->create(new Product('Градежен материјал (тон)', $goodsCategoryId, '150000.00')),
];

$serviceIds = [
    'webapp' => $serviceRepo->create(new Service('Изработка на веб-апликација (рата)', $servicesCategoryId, '60000.00')),
    'hosting' => $serviceRepo->create(new Service('Годишен хостинг', $servicesCategoryId, '3600.00')),
    'smetkovodstvo' => $serviceRepo->create(new Service('Сметководствени услуги (месечно)', $servicesCategoryId, '8000.00')),
    'transport' => $serviceRepo->create(new Service('Транспорт', $servicesCategoryId, '5000.00')),
];
echo "Создадени " . count($productIds) . " производи и " . count($serviceIds) . " услуги\n";

// --- Партнери -----------------------------------------------------------

$partners = [
    'alfa' => new Partner($markerName, 'customer', '4030995123456', 'ул. Македонија бр. 10, Скопје', 'contact@alfa-trgovija.mk', 'MK'),
    'beta' => new Partner('Бета Софтвер ДОО', 'customer', '4020998234567', 'бул. Партизански одреди бр. 5, Скопје', 'info@beta-soft.mk', 'MK'),
    'gama' => new Partner('Гама Дистрибуција ДООЕЛ', 'supplier', '4010997345678', 'ул. Индустриска бб, Битола', 'gama@gama-dist.mk', 'MK'),
    'delta' => new Partner('Делта Консалтинг', 'both', '4009996456789', 'ул. Мајка Тереза бр. 3, Скопје', 'delta@delta-consulting.mk', 'MK'),
    'epsilon' => new Partner('Епсилон Градежништво ДОО', 'customer', '4008995567890', 'ул. Илинденска бр. 22, Тетово', 'epsilon@epsilon-gradba.mk', 'MK'),
    'zeta' => new Partner('Зета Услуги ДООЕЛ', 'supplier', '4007994678901', 'ул. Борис Трајковски бр. 8, Куманово', 'zeta@zeta-uslugi.mk', 'MK'),
    'omega' => new Partner('Omega Trading GmbH', 'customer', 'DE123456789', 'Hauptstrasse 1, Berlin', 'info@omega-trading.de', 'DE'),
];

$partnerIds = [];
foreach ($partners as $key => $partner) {
    $partnerIds[$key] = $partnerRepo->create($partner);
    echo "Создаден партнер: {$partner->name}\n";
}

// --- Фактури --------------------------------------------------------------

$invoicesToCreate = [
    ['alfa', '2026-07-05', '2026-08-04', 'issued_paid', [
        ['type' => 'product', 'item_id' => $productIds['materijali'], 'quantity' => '10'],
        ['type' => 'product', 'item_id' => $productIds['toner'], 'quantity' => '3'],
    ]],
    ['alfa', '2026-08-01', '2026-08-31', 'issued', [
        ['type' => 'product', 'item_id' => $productIds['materijali'], 'quantity' => '5', 'unit_price' => '500.00'],
    ]],
    ['beta', '2026-07-20', '2026-08-19', 'issued_paid', [
        ['type' => 'service', 'item_id' => $serviceIds['webapp'], 'quantity' => '1'],
    ]],
    ['beta', '2026-08-10', '2026-09-09', 'draft', [
        ['type' => 'service', 'item_id' => $serviceIds['webapp'], 'quantity' => '1'],
        ['type' => 'service', 'item_id' => $serviceIds['hosting'], 'quantity' => '1'],
    ]],
    ['delta', '2026-08-12', '2026-09-11', 'issued', [
        ['type' => 'service', 'item_id' => $serviceIds['smetkovodstvo'], 'quantity' => '1'],
    ]],
    ['epsilon', '2026-07-28', '2026-08-27', 'issued_paid', [
        ['type' => 'product', 'item_id' => $productIds['gradezen'], 'quantity' => '1'],
        ['type' => 'service', 'item_id' => $serviceIds['transport'], 'quantity' => '1'],
    ]],
    ['omega', '2026-08-14', '2026-09-13', 'issued', [
        ['type' => 'service', 'item_id' => $serviceIds['smetkovodstvo'], 'quantity' => '1'],
    ]],
];

foreach ($invoicesToCreate as [$partnerKey, $date, $dueDate, $mode, $lines]) {
    $invoiceId = $invoiceService->createInvoice($partnerIds[$partnerKey], $date, $dueDate, $lines);

    if ($mode === 'issued' || $mode === 'issued_paid') {
        $invoiceService->issue($invoiceId);
    }

    if ($mode === 'issued_paid') {
        $invoiceService->markPaid($invoiceId);
    }

    echo "Создадена фактура за партнер '$partnerKey' ($date, статус: $mode)\n";
}

// --- Категории на трошоци + влезни фактури ---------------------------------
// Огледало на продажната страна, но сметката е обврска/трошок, не приход, а
// ДДВ стапката се внесува рачно по линија (не се резолвира од категоријата).

$goodsForResale = $accountRepo->findByCode('660'); // Стоки на залиха
$externalServices = $accountRepo->findByCode('419'); // Останати услуги
$rentExpense = $accountRepo->findByCode('414'); // Наемнини - лизинг

$expenseCategoryIds = [
    'stoki' => $expenseCategoryRepo->create(new ExpenseCategory('Набавка на стоки за препродажба', $goodsForResale->id, $goodsForResale->id, 'full')),
    'usluzi' => $expenseCategoryRepo->create(new ExpenseCategory('Надворешни услуги', $externalServices->id, null, 'full')),
    'naem' => $expenseCategoryRepo->create(new ExpenseCategory('Наемнина', $rentExpense->id, null, 'full')),
];
echo "Создадени " . count($expenseCategoryIds) . " категории на трошоци\n";

$purchaseInvoicesToCreate = [
    ['gama', 'ГАМА-2026-118', '2026-07-08', '2026-08-07', 'posted_paid', [
        ['category_id' => $expenseCategoryIds['stoki'], 'quantity' => '20', 'unit_price' => '2500.00', 'vat_rate_id' => $vatStandard],
    ]],
    ['zeta', 'ZETA-0044', '2026-08-03', '2026-09-02', 'posted', [
        ['category_id' => $expenseCategoryIds['usluzi'], 'quantity' => '1', 'unit_price' => '9500.00', 'vat_rate_id' => $vatReduced, 'description' => 'Одржување опрема'],
    ]],
    ['gama', 'ГАМА-2026-131', '2026-08-15', '2026-09-14', 'draft', [
        ['category_id' => $expenseCategoryIds['naem'], 'quantity' => '1', 'unit_price' => '18000.00', 'vat_rate_id' => $vatStandard],
    ]],
];

foreach ($purchaseInvoicesToCreate as [$partnerKey, $supplierNumber, $date, $dueDate, $mode, $lines]) {
    $purchaseInvoiceId = $purchaseInvoiceService->createPurchaseInvoice($partnerIds[$partnerKey], $supplierNumber, $date, $dueDate, $lines);

    if ($mode === 'posted' || $mode === 'posted_paid') {
        $purchaseInvoiceService->post($purchaseInvoiceId);
    }

    if ($mode === 'posted_paid') {
        $purchaseInvoiceService->markPaid($purchaseInvoiceId);
    }

    echo "Создадена влезна фактура за партнер '$partnerKey' ($date, статус: $mode)\n";
}

// --- Рачни journal записи (надвор од фактурирање) -------------------------

$bank = $accountRepo->findByCode('100');
$capital = $accountRepo->findByCode('912');
$bankFees = $accountRepo->findByCode('447');
$rent = $accountRepo->findByCode('414');
$utilities = $accountRepo->findByCode('419');

$ledgerService->postEntry('2026-07-01', 'Основачки влог во готово', 'ВЛОГ-001', [
    ['account_id' => $bank->id, 'debit' => '500000.00', 'credit' => '0'],
    ['account_id' => $capital->id, 'debit' => '0', 'credit' => '500000.00'],
]);
echo "Создаден journal запис: основачки влог\n";

$ledgerService->postEntry('2026-07-10', 'Закупнина за деловен простор — јули', 'ФАК-ЗАКУП-07', [
    ['account_id' => $rent->id, 'debit' => '18000.00', 'credit' => '0'],
    ['account_id' => $bank->id, 'debit' => '0', 'credit' => '18000.00'],
]);
echo "Создаден journal запис: закупнина јули\n";

$ledgerService->postEntry('2026-08-05', 'Струја и вода — август', 'СМЕТКА-АВГ', [
    ['account_id' => $utilities->id, 'debit' => '4200.00', 'credit' => '0'],
    ['account_id' => $bank->id, 'debit' => '0', 'credit' => '4200.00'],
]);
echo "Создаден journal запис: комунални август\n";

$ledgerService->postEntry('2026-08-10', 'Банкарски провизии — август', 'ИЗВОД-08', [
    ['account_id' => $bankFees->id, 'debit' => '350.00', 'credit' => '0'],
    ['account_id' => $bank->id, 'debit' => '0', 'credit' => '350.00'],
]);
echo "Создаден journal запис: банкарски провизии\n";

echo "Готово.\n";
