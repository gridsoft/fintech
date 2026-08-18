<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\ExpenseCategory;
use App\Repository\AccountRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\PurchaseInvoiceService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PurchaseInvoiceServiceTest extends TestCase
{
    private PDO $db;
    private PurchaseInvoiceRepository $invoices;
    private PurchaseInvoiceService $service;
    private int $partnerId;
    private int $foreignPartnerId;
    private int $vatStandardId;
    private int $vatNoInputId;
    private int $categoryId;
    private int $nonDeductibleCategoryId;
    private int $reverseChargeCategoryId;
    private int $capitalizableCategoryId;

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->invoices = new PurchaseInvoiceRepository();
        $this->service = new PurchaseInvoiceService($this->invoices);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'supplier', 'MK')");
        $stmt->execute(['Тест добавувач ' . uniqid()]);
        $this->partnerId = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'supplier', 'DE')");
        $stmt->execute(['Тест странски добавувач ' . uniqid()]);
        $this->foreignPartnerId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $expenseAccount = $accounts->findByCode('419');
        $vatReceivable = $accounts->findByCode('160');

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест стандардна', '18.00', 'standard', null, $vatReceivable->id));
        $this->vatNoInputId = $vatRateRepo->create(new VatRate('Тест без сметка', '18.00', 'standard', null, null));

        $categoryRepo = new ExpenseCategoryRepository();
        $this->categoryId = $categoryRepo->create(new ExpenseCategory(
            'Тест категорија трошок ' . uniqid(),
            $expenseAccount->id,
            null,
            'full'
        ));
        $this->nonDeductibleCategoryId = $categoryRepo->create(new ExpenseCategory(
            'Тест неодбивна категорија ' . uniqid(),
            $expenseAccount->id,
            null,
            'none'
        ));
        $this->reverseChargeCategoryId = $categoryRepo->create(new ExpenseCategory(
            'Тест reverse charge категорија ' . uniqid(),
            $expenseAccount->id,
            null,
            'full',
            false,
            null,
            true
        ));
        $this->capitalizableCategoryId = $categoryRepo->create(new ExpenseCategory(
            'Тест средство категорија без век ' . uniqid(),
            $expenseAccount->id,
            null,
            'full',
            true,
            null
        ));
    }

    protected function tearDown(): void
    {
        $this->db->prepare("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id IN (?, ?))")
            ->execute([$this->partnerId, $this->foreignPartnerId]);
        $this->db->prepare("DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id IN (?, ?))")
            ->execute([$this->partnerId, $this->foreignPartnerId]);
        $this->db->prepare('DELETE FROM purchase_invoices WHERE partner_id IN (?, ?)')->execute([$this->partnerId, $this->foreignPartnerId]);
        $this->db->prepare('DELETE FROM expense_categories WHERE id IN (?, ?, ?, ?)')->execute([
            $this->categoryId, $this->nonDeductibleCategoryId, $this->reverseChargeCategoryId, $this->capitalizableCategoryId,
        ]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id IN (?, ?)')->execute([$this->vatStandardId, $this->vatNoInputId]);
        $this->db->prepare('DELETE FROM partners WHERE id IN (?, ?)')->execute([$this->partnerId, $this->foreignPartnerId]);
    }

    public function test_it_resolves_account_from_category_and_freezes_it_on_the_line(): void
    {
        $invoiceId = $this->service->createPurchaseInvoice($this->partnerId, 'SUP-001', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->categoryId, 'quantity' => '2', 'unit_price' => '1000.00', 'vat_rate_id' => $this->vatStandardId],
        ]);

        $invoice = $this->invoices->find($invoiceId);

        $this->assertSame('2000.00', $invoice->totalNet);
        $this->assertSame('360.00', $invoice->totalVat);
        $this->assertSame('2360.00', $invoice->totalGross);
    }

    public function test_it_rejects_duplicate_supplier_invoice_number_for_same_partner(): void
    {
        $this->service->createPurchaseInvoice($this->partnerId, 'SUP-DUP', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->categoryId, 'quantity' => '1', 'unit_price' => '100.00', 'vat_rate_id' => $this->vatStandardId],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->createPurchaseInvoice($this->partnerId, 'SUP-DUP', '2026-02-01', '2026-02-28', [
            ['category_id' => $this->categoryId, 'quantity' => '1', 'unit_price' => '50.00', 'vat_rate_id' => $this->vatStandardId],
        ]);
    }

    public function test_it_rejects_reverse_charge_category_until_that_flow_exists(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createPurchaseInvoice($this->partnerId, 'SUP-RC', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->reverseChargeCategoryId, 'quantity' => '1', 'unit_price' => '100.00', 'vat_rate_id' => $this->vatStandardId],
        ]);
    }

    public function test_it_rejects_capitalizable_category_without_a_configured_useful_life(): void
    {
        // Основни средства СЕ поддржани (Фаза 8), но категоријата мора да
        // има поставено default_useful_life_months пред да се користи —
        // inak нема од каде да се земе амортизацискиот век.
        $this->expectException(RuntimeException::class);

        $this->service->createPurchaseInvoice($this->partnerId, 'SUP-CAP', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->capitalizableCategoryId, 'quantity' => '1', 'unit_price' => '100.00', 'vat_rate_id' => $this->vatStandardId],
        ]);
    }

    public function test_posting_generates_a_balanced_journal_entry_with_deductible_vat_split_out(): void
    {
        $invoiceId = $this->service->createPurchaseInvoice($this->partnerId, 'SUP-002', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->categoryId, 'quantity' => '1', 'unit_price' => '1000.00', 'vat_rate_id' => $this->vatStandardId],
        ]);

        $this->service->post($invoiceId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('posted', $invoice->status);
        $this->assertNotNull($invoice->journalEntryId);

        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?'
        );
        $stmt->execute([$invoice->journalEntryId]);
        $sums = $stmt->fetch();

        $this->assertEquals(1180.00, (float) $sums['d']);
        $this->assertEquals(1180.00, (float) $sums['c']);

        // 3 реда: дебит трошок + дебит влезен ДДВ + кредит обврски кон добавувач
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$invoice->journalEntryId]);
        $this->assertSame(3, (int) $stmt->fetchColumn());

        // Обврските (кредит редот) мора да се таговирани со партнерот
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ? AND partner_id = ? AND credit > 0');
        $stmt->execute([$invoice->journalEntryId, $this->partnerId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function test_non_deductible_vat_is_folded_into_the_expense_account_not_split_out(): void
    {
        $invoiceId = $this->service->createPurchaseInvoice($this->partnerId, 'SUP-003', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->nonDeductibleCategoryId, 'quantity' => '1', 'unit_price' => '1000.00', 'vat_rate_id' => $this->vatStandardId],
        ]);

        $this->service->post($invoiceId);

        $invoice = $this->invoices->find($invoiceId);

        // 2 реда наместо 3: нема посебен ред за ДДВ, целото 1180 оди на трошокот
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$invoice->journalEntryId]);
        $this->assertSame(2, (int) $stmt->fetchColumn());

        $stmt = $this->db->prepare('SELECT debit FROM journal_lines WHERE journal_entry_id = ? AND debit > 0');
        $stmt->execute([$invoice->journalEntryId]);
        $this->assertEquals(1180.00, (float) $stmt->fetchColumn());
    }

    public function test_it_fails_to_post_when_deductible_vat_rate_has_no_receivable_account(): void
    {
        $invoiceId = $this->service->createPurchaseInvoice($this->partnerId, 'SUP-004', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->categoryId, 'quantity' => '1', 'unit_price' => '100.00', 'vat_rate_id' => $this->vatNoInputId],
        ]);

        $this->expectException(RuntimeException::class);

        $this->service->post($invoiceId);
    }

    public function test_it_cannot_post_the_same_invoice_twice(): void
    {
        $invoiceId = $this->service->createPurchaseInvoice($this->partnerId, 'SUP-005', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->categoryId, 'quantity' => '1', 'unit_price' => '100.00', 'vat_rate_id' => $this->vatStandardId],
        ]);

        $this->service->post($invoiceId);

        $this->expectException(RuntimeException::class);
        $this->service->post($invoiceId);
    }

    public function test_it_requires_at_least_one_line(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createPurchaseInvoice($this->partnerId, 'SUP-006', '2026-01-01', '2026-01-31', []);
    }
}
