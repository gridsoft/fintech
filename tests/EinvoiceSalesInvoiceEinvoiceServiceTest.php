<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\Product;
use App\Domain\Invoicing\ProductCategory;
use App\Repository\AccountRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\VatRateRepository;
use App\Service\Einvoice\SalesInvoiceEinvoiceService;
use App\Service\InvoiceService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Без клиент/сертификат сè уште — овој тест го верификува единствено нешто
 * што МОЖЕ да се провери сега: копчето „Прати како е-фактура“ мора јасно да
 * откаже (не тивко да „успее“) кога UJP_EINVOICE_* не се конфигурирани, и
 * фактурата останува следлива со записана грешка, не лажно означена sent.
 */
class EinvoiceSalesInvoiceEinvoiceServiceTest extends TestCase
{
    private PDO $db;
    private InvoiceRepository $invoices;
    private InvoiceService $invoiceService;
    private SalesInvoiceEinvoiceService $einvoiceService;
    private int $partnerId;
    private int $vatRateId;
    private int $categoryId;
    private int $productId;
    private array $createdInvoiceIds = [];
    private array $createdEntryIds = [];

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->invoices = new InvoiceRepository();
        $this->invoiceService = new InvoiceService($this->invoices);
        $this->einvoiceService = new SalesInvoiceEinvoiceService($this->invoices);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country, tax_number) VALUES (?, 'customer', 'MK', '4030995135699')");
        $stmt->execute(['Тест купувач ' . uniqid()]);
        $this->partnerId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');
        $vatPayable = $accounts->findByCode('260');

        $vatRateRepo = new VatRateRepository();
        $this->vatRateId = $vatRateRepo->create(new VatRate('Тест 18%', '18.00', 'standard', $vatPayable->id, null, true, null, 'DDV-A'));

        $categoryRepo = new ProductCategoryRepository();
        $this->categoryId = $categoryRepo->create(new ProductCategory(
            'Тест категорија ' . uniqid(),
            $revenue->id,
            $this->vatRateId,
            $revenue->id,
            $this->vatRateId
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
        $this->db->prepare('DELETE FROM vat_rates WHERE id = ?')->execute([$this->vatRateId]);
        $this->db->prepare('DELETE FROM partners WHERE id = ?')->execute([$this->partnerId]);
    }

    public function test_it_refuses_a_draft_invoice(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->partnerId, '2026-08-19', '2026-09-18', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Само издадена фактура/');

        $this->einvoiceService->send($invoiceId);
    }

    public function test_it_records_a_clear_error_when_ujp_is_not_configured(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->partnerId, '2026-08-19', '2026-09-18', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;
        $this->invoiceService->issue($invoiceId);
        $this->createdEntryIds[] = $this->invoices->find($invoiceId)->journalEntryId;

        try {
            $this->einvoiceService->send($invoiceId);
            $this->fail('Очекував исклучок бидејќи УЈП не е конфигуриран.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('не е конфигур', $e->getMessage());
        }

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('error', $invoice->einvoiceStatus);
        $this->assertNull($invoice->einvoiceEuid);
        $this->assertNotNull($invoice->einvoiceError);
    }
}
