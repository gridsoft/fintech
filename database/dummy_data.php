<?php

/**
 * Dummy тест-податоци за рачно истражување на апликацијата: партнери, неколку
 * фактури во различни статуси и рачни journal записи. Минува низ вистинските
 * сервиси (InvoiceService/LedgerService) за да сè биде реално и балансирано.
 *
 * Безбедно за повторно извршување — ако партнерот-маркер веќе постои, излегува без промени.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Domain\Partners\Partner;
use App\Repository\AccountRepository;
use App\Repository\NalogRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerkRepository;
use App\Service\InvoiceService;
use App\Service\LedgerService;

$pdo = Database::connection();
$partnerRepo = new PartnerRepository();
$accountRepo = new AccountRepository();
$nalogRepo = new NalogRepository();
$terkRepo = new TerkRepository();
$invoiceService = new InvoiceService();
$ledgerService = new LedgerService();

$defaultNalog = $nalogRepo->all()[0] ?? null;
$defaultTerk = $terkRepo->all()[0] ?? null;

if (!$defaultNalog || !$defaultTerk) {
    fwrite(STDERR, "Нема дефиниран налог/терк — пушти прво php database/migrate.php.\n");
    exit(1);
}

$markerName = 'Алфа Трговија ДООЕЛ';
foreach ($partnerRepo->all() as $existing) {
    if ($existing->name === $markerName) {
        echo "Dummy податоците веќе постојат (пронајден '$markerName') — прескокнувам.\n";
        exit(0);
    }
}

// --- Партнери -----------------------------------------------------------

$partners = [
    'alfa' => new Partner($markerName, 'customer', '4030995123456', 'ул. Македонија бр. 10, Скопје', 'contact@alfa-trgovija.mk'),
    'beta' => new Partner('Бета Софтвер ДОО', 'customer', '4020998234567', 'бул. Партизански одреди бр. 5, Скопје', 'info@beta-soft.mk'),
    'gama' => new Partner('Гама Дистрибуција ДООЕЛ', 'supplier', '4010997345678', 'ул. Индустриска бб, Битола', 'gama@gama-dist.mk'),
    'delta' => new Partner('Делта Консалтинг', 'both', '4009996456789', 'ул. Мајка Тереза бр. 3, Скопје', 'delta@delta-consulting.mk'),
    'epsilon' => new Partner('Епсилон Градежништво ДОО', 'customer', '4008995567890', 'ул. Илинденска бр. 22, Тетово', 'epsilon@epsilon-gradba.mk'),
    'zeta' => new Partner('Зета Услуги ДООЕЛ', 'supplier', '4007994678901', 'ул. Борис Трајковски бр. 8, Куманово', 'zeta@zeta-uslugi.mk'),
];

$partnerIds = [];
foreach ($partners as $key => $partner) {
    $partnerIds[$key] = $partnerRepo->create($partner);
    echo "Создаден партнер: {$partner->name}\n";
}

// --- Фактури --------------------------------------------------------------

$invoicesToCreate = [
    ['alfa', '2026-07-05', '2026-08-04', 'issued_paid', [
        ['description' => 'Канцелариски материјали', 'quantity' => '10', 'unit_price' => '450.00', 'vat_rate' => '18'],
        ['description' => 'Тонер за печатач', 'quantity' => '3', 'unit_price' => '1200.00', 'vat_rate' => '18'],
    ]],
    ['alfa', '2026-08-01', '2026-08-31', 'issued', [
        ['description' => 'Месечна испорака на стоки', 'quantity' => '1', 'unit_price' => '25000.00', 'vat_rate' => '18'],
    ]],
    ['beta', '2026-07-20', '2026-08-19', 'issued_paid', [
        ['description' => 'Изработка на веб-апликација — прва рата', 'quantity' => '1', 'unit_price' => '60000.00', 'vat_rate' => '18'],
    ]],
    ['beta', '2026-08-10', '2026-09-09', 'draft', [
        ['description' => 'Изработка на веб-апликација — втора рата', 'quantity' => '1', 'unit_price' => '60000.00', 'vat_rate' => '18'],
        ['description' => 'Хостинг (годишен)', 'quantity' => '1', 'unit_price' => '3600.00', 'vat_rate' => '18'],
    ]],
    ['delta', '2026-08-12', '2026-09-11', 'issued', [
        ['description' => 'Сметководствени услуги — август', 'quantity' => '1', 'unit_price' => '8000.00', 'vat_rate' => '5'],
    ]],
    ['epsilon', '2026-07-28', '2026-08-27', 'issued_paid', [
        ['description' => 'Градежен материјал', 'quantity' => '1', 'unit_price' => '150000.00', 'vat_rate' => '18'],
        ['description' => 'Транспорт', 'quantity' => '1', 'unit_price' => '5000.00', 'vat_rate' => '5'],
    ]],
];

foreach ($invoicesToCreate as [$partnerKey, $date, $dueDate, $mode, $lines]) {
    $invoiceId = $invoiceService->createInvoice($partnerIds[$partnerKey], $defaultNalog->id, $date, $dueDate, $lines);

    if ($mode === 'issued' || $mode === 'issued_paid') {
        $invoiceService->issue($invoiceId, $defaultTerk->id);
    }

    if ($mode === 'issued_paid') {
        $invoiceService->markPaid($invoiceId);
    }

    echo "Создадена фактура за партнер '$partnerKey' ($date, статус: $mode)\n";
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
