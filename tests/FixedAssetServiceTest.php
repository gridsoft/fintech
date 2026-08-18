<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\ExpenseCategory;
use App\Repository\AccountRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\FixedAssetRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\FixedAssetService;
use App\Service\PurchaseInvoiceService;
use PDO;
use PHPUnit\Framework\TestCase;

class FixedAssetServiceTest extends TestCase
{
    private PDO $db;
    private FixedAssetRepository $assets;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private FixedAssetService $service;
    private PurchaseInvoiceService $purchaseInvoiceService;

    private int $supplierId;
    private int $vatStandardId;
    private int $equipmentAccountId;
    private int $expenseCategoryId;

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->assets = new FixedAssetRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
        $this->service = new FixedAssetService($this->assets);
        $this->purchaseInvoiceService = new PurchaseInvoiceService($this->purchaseInvoices, null, null, null, null, null, $this->service);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'supplier', 'MK')");
        $stmt->execute(['Тест добавувач ОС ' . uniqid()]);
        $this->supplierId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $vatPayable = $accounts->findByCode('260');
        $vatReceivable = $accounts->findByCode('160');
        $this->equipmentAccountId = $accounts->findByCode('022')->id;

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест стандардна ОС', '18.00', 'standard', $vatPayable->id, $vatReceivable->id));

        // Стапка 100% годишно = идентичен месечен износ како стариот "12
        // месеци" фикстур (value/12) — 12 * 8.333...% = 100%, го задржува
        // истото аритметичко однесување за остатокот на тестовите подолу.
        $expenseCategoryRepo = new ExpenseCategoryRepository();
        $this->expenseCategoryId = $expenseCategoryRepo->create(new ExpenseCategory(
            'Тест опрема ' . uniqid(),
            $this->equipmentAccountId,
            null,
            'full',
            true,
            '100.00'
        ));
    }

    protected function tearDown(): void
    {
        $assetIds = $this->db->query("SELECT id FROM fixed_assets WHERE purchase_invoice_line_id IN (SELECT id FROM purchase_invoice_lines WHERE expense_category_id = {$this->expenseCategoryId})")->fetchAll(PDO::FETCH_COLUMN);

        if ($assetIds) {
            $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
            $this->db->prepare("DELETE FROM fixed_asset_depreciation_entries WHERE fixed_asset_id IN ($placeholders)")->execute($assetIds);
            $this->db->prepare("DELETE FROM fixed_assets WHERE id IN ($placeholders)")->execute($assetIds);
        }

        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id = ?)')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id = ?)')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM purchase_invoices WHERE partner_id = ?')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM expense_categories WHERE id = ?')->execute([$this->expenseCategoryId]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id = ?')->execute([$this->vatStandardId]);
        $this->db->prepare('DELETE FROM partners WHERE id = ?')->execute([$this->supplierId]);
    }

    private function createAndPostInvoice(string $supplierNumber, string $unitPrice): int
    {
        $invoiceId = $this->purchaseInvoiceService->createPurchaseInvoice($this->supplierId, $supplierNumber, '2026-01-01', '2026-01-31', [
            ['category_id' => $this->expenseCategoryId, 'quantity' => '1', 'unit_price' => $unitPrice, 'vat_rate_id' => $this->vatStandardId, 'description' => 'Тест опрема — набавка'],
        ]);
        $this->purchaseInvoiceService->post($invoiceId);

        return $invoiceId;
    }

    public function test_posting_a_capitalizable_purchase_invoice_creates_a_fixed_asset(): void
    {
        $this->createAndPostInvoice('OS-001', '12000.00');

        $stmt = $this->db->prepare('SELECT * FROM fixed_assets WHERE purchase_invoice_line_id IN (SELECT id FROM purchase_invoice_lines WHERE expense_category_id = ?)');
        $stmt->execute([$this->expenseCategoryId]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'основното средство треба да е создадено');
        $this->assertSame('12000.00', $row['purchase_value']);
        $this->assertSame('100.00', $row['annual_rate']);
        $this->assertSame($this->equipmentAccountId, (int) $row['account_id']);
        $this->assertSame('Тест опрема — набавка', $row['name']);
    }

    public function test_run_depreciation_posts_balanced_entry_and_records_per_asset(): void
    {
        // runDepreciation() намерно работи глобално врз сите активни
        // средства (реален месечен процес) — не претпоставуваме дека
        // нашето тест-средство е единственото активно во базата, само дека
        // ги содржи неговите ефекти (>= 1 средство засегнато вкупно).
        $this->createAndPostInvoice('OS-002', '12000.00');
        $asset = $this->firstAssetForCategory();

        $result = $this->service->runDepreciation('2026-02-28');

        $this->assertGreaterThanOrEqual(1, $result['count']);

        $entries = $this->assets->depreciationEntriesForAsset($asset->id);
        $this->assertCount(1, $entries);
        $this->assertSame('1000.00', $entries[0]->amount);

        // Записот е ЗАЕДНИЧКИ за сите средства засегнати во истиот период
        // (не еден по средство) — затоа проверуваме дека е балансиран
        // (дебит==кредит) и дека вкупниот износ е БАРЕМ нашите 1000.00, не
        // дека е ТОЧНО 1000.00 (може да содржи и други активни средства).
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$entries[0]->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals((float) $sums['d'], (float) $sums['c']);
        $this->assertGreaterThanOrEqual(1000.00, (float) $sums['d']);
    }

    public function test_running_depreciation_twice_for_same_period_is_idempotent(): void
    {
        $this->createAndPostInvoice('OS-003', '12000.00');

        $this->service->runDepreciation('2026-02-28');
        $second = $this->service->runDepreciation('2026-02-28');

        $this->assertSame(0, $second['count']);

        $asset = $this->firstAssetForCategory();
        $this->assertCount(1, $this->assets->depreciationEntriesForAsset($asset->id));
    }

    public function test_final_period_caps_at_remaining_value_instead_of_overshooting(): void
    {
        // 10000.00 / 12 месеци се крати (truncate) на 833.33/месец, па
        // 12 * 833.33 = 9999.96 — секогаш ЌЕ ПОМАЛКУ или еднакво на
        // purchase_value со bcdiv truncation, никогаш повеќе. Затоа
        // капирањето не активира на номиналниот 12-ти месец (преостанато
        // сепак >= стандардниот месечен износ таму), туку дури на 13-от,
        // "чистечки" период кој го фаќа остатокот од заокружувањето.
        $this->createAndPostInvoice('OS-004', '10000.00');
        $asset = $this->firstAssetForCategory();

        for ($month = 1; $month <= 12; $month++) {
            $this->service->runDepreciation(sprintf('2026-%02d-28', $month));
        }

        $accumulatedAfterSchedule = $this->assets->accumulatedDepreciation($asset->id);
        $this->assertSame('9999.96', $accumulatedAfterSchedule, '12 * 833.33 (заокружено кон долу)');

        $this->service->runDepreciation('2027-01-31');

        $accumulatedFinal = $this->assets->accumulatedDepreciation($asset->id);
        $this->assertSame('10000.00', $accumulatedFinal, 'вкупно амортизирано никогаш не смее да ја надмине набавната вредност');

        $entries = $this->assets->depreciationEntriesForAsset($asset->id);
        $lastEntry = end($entries);
        $this->assertSame('0.04', $lastEntry->amount, 'капирано на преостанатото, не на стандардниот месечен износ 833.33');

        // Целосно амортизирано — веќе не треба да се вклучи во идно извршување (за ова конкретно средство).
        $this->service->runDepreciation('2027-02-28');
        $this->assertCount(13, $this->assets->depreciationEntriesForAsset($asset->id), 'нема нов запис по целосна амортизација');
    }

    public function test_run_depreciation_skips_assets_not_yet_purchased_in_that_period(): void
    {
        $this->createAndPostInvoice('OS-005', '12000.00'); // набавено 2026-01-01
        $asset = $this->firstAssetForCategory();

        $this->service->runDepreciation('2025-12-31'); // период ПРЕД набавката

        $this->assertCount(0, $this->assets->depreciationEntriesForAsset($asset->id), 'не смее да амортизира средство пред неговиот датум на набавка');
        $this->assertSame('0.00', $this->assets->accumulatedDepreciation($asset->id));
    }

    private function firstAssetForCategory()
    {
        $stmt = $this->db->prepare('SELECT id FROM fixed_assets WHERE purchase_invoice_line_id IN (SELECT id FROM purchase_invoice_lines WHERE expense_category_id = ?) ORDER BY id DESC LIMIT 1');
        $stmt->execute([$this->expenseCategoryId]);
        $id = (int) $stmt->fetchColumn();

        return $this->assets->find($id);
    }
}
