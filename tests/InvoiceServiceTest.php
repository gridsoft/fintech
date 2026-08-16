<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\ProductCategory;
use App\Domain\Invoicing\Product;
use App\Repository\AccountRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\VatRateRepository;
use App\Service\InvoiceService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InvoiceServiceTest extends TestCase
{
    private PDO $db;
    private InvoiceRepository $invoices;
    private InvoiceService $service;
    private int $partnerId;
    private int $foreignPartnerId;
    private int $vatStandardId;
    private int $vatZeroId;
    private int $categoryId;
    private int $productId;
    private array $createdInvoiceIds = [];
    private array $createdEntryIds = [];

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->invoices = new InvoiceRepository();
        $this->service = new InvoiceService($this->invoices);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'customer', 'MK')");
        $stmt->execute(['Тест партнер ' . uniqid()]);
        $this->partnerId = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'customer', 'DE')");
        $stmt->execute(['Тест странски партнер ' . uniqid()]);
        $this->foreignPartnerId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');
        $vatPayable = $accounts->findByCode('260');

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест стандардна', '18.00', 'standard', $vatPayable->id));
        $this->vatZeroId = $vatRateRepo->create(new VatRate('Тест нулта', '0.00', 'zero', null));

        $categoryRepo = new ProductCategoryRepository();
        $this->categoryId = $categoryRepo->create(new ProductCategory(
            'Тест категорија ' . uniqid(),
            $revenue->id,
            $this->vatStandardId,
            $revenue->id,
            $this->vatZeroId
        ));

        $productRepo = new ProductRepository();
        $this->productId = $productRepo->create(new Product('Тест производ', $this->categoryId, '1000.00'));
    }

    protected function tearDown(): void
    {
        foreach ($this->createdInvoiceIds as $id) {
            $this->db->prepare('DELETE FROM invoices WHERE id = ?')->execute([$id]);
        }

        foreach ($this->createdEntryIds as $id) {
            $this->db->prepare('DELETE FROM journal_entries WHERE id = ?')->execute([$id]);
        }

        $this->db->prepare('DELETE FROM products WHERE id = ?')->execute([$this->productId]);
        $this->db->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$this->categoryId]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id IN (?, ?)')->execute([$this->vatStandardId, $this->vatZeroId]);
        $this->db->prepare('DELETE FROM partners WHERE id IN (?, ?)')->execute([$this->partnerId, $this->foreignPartnerId]);
    }

    public function test_it_resolves_account_and_vat_from_category_for_domestic_partner(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '2'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $invoice = $this->invoices->find($invoiceId);

        // 2 * 1000 = 2000 нето, 18% ддв = 360
        $this->assertSame('2000.00', $invoice->totalNet);
        $this->assertSame('360.00', $invoice->totalVat);
        $this->assertSame('2360.00', $invoice->totalGross);
        $this->assertSame($this->vatStandardId, $invoice->lines[0]->vatRateId);
    }

    public function test_it_resolves_foreign_context_for_foreign_partner(): void
    {
        $invoiceId = $this->service->createInvoice($this->foreignPartnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $invoice = $this->invoices->find($invoiceId);

        // странски контекст → нулта ДДВ стапка од категоријата
        $this->assertSame($this->vatZeroId, $invoice->lines[0]->vatRateId);
        $this->assertSame('0.00', $invoice->totalVat);
    }

    public function test_unit_price_defaults_to_product_price_when_not_overridden(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '3'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $invoice = $this->invoices->find($invoiceId);

        $this->assertSame('1000.00', $invoice->lines[0]->unitPrice);
    }

    public function test_issuing_generates_a_balanced_journal_entry(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $this->service->issue($invoiceId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('issued', $invoice->status);
        $this->assertNotNull($invoice->journalEntryId);
        $this->createdEntryIds[] = $invoice->journalEntryId;

        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?'
        );
        $stmt->execute([$invoice->journalEntryId]);
        $sums = $stmt->fetch();

        $this->assertEquals(1180.00, (float) $sums['d']);
        $this->assertEquals(1180.00, (float) $sums['c']);

        // Побарувањата (дебит редот) мора да се таговирани со партнерот
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ? AND partner_id = ?'
        );
        $stmt->execute([$invoice->journalEntryId, $this->partnerId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function test_lines_with_same_resolved_account_are_grouped_on_posting(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $this->service->issue($invoiceId);

        $invoice = $this->invoices->find($invoiceId);
        $this->createdEntryIds[] = $invoice->journalEntryId;

        // 1 дебит (побарувања) + 1 кредит (иста приходна сметка, групирано) + 1 кредит (ддв) = 3 редови, не 4+
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$invoice->journalEntryId]);
        $this->assertSame(3, (int) $stmt->fetchColumn());
    }

    public function test_it_cannot_issue_the_same_invoice_twice(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $this->service->issue($invoiceId);
        $invoice = $this->invoices->find($invoiceId);
        $this->createdEntryIds[] = $invoice->journalEntryId;

        $this->expectException(RuntimeException::class);
        $this->service->issue($invoiceId);
    }

    public function test_it_requires_at_least_one_line(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', []);
    }
}
