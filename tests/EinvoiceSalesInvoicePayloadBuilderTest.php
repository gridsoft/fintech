<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\Product;
use App\Domain\Invoicing\ProductCategory;
use App\Repository\AccountRepository;
use App\Repository\CurrencyRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\VatRateRepository;
use App\Service\Einvoice\EinvoiceConfig;
use App\Service\Einvoice\SalesInvoicePayloadBuilder;
use App\Service\InvoiceService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EinvoiceSalesInvoicePayloadBuilderTest extends TestCase
{
    private PDO $db;
    private InvoiceRepository $invoices;
    private PartnerRepository $partners;
    private InvoiceService $invoiceService;
    private SalesInvoicePayloadBuilder $builder;
    private int $partnerId;
    private int $vatRateId;
    private int $categoryId;
    private int $productId;
    private array $createdInvoiceIds = [];

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->invoices = new InvoiceRepository();
        $this->partners = new PartnerRepository();
        $this->invoiceService = new InvoiceService($this->invoices);
        $this->builder = new SalesInvoicePayloadBuilder();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country, tax_number) VALUES (?, 'customer', 'MK', '4030995135699')");
        $stmt->execute(['Тест купувач ' . uniqid()]);
        $this->partnerId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');

        $vatRateRepo = new VatRateRepository();
        $this->vatRateId = $vatRateRepo->create(new VatRate('Тест 18%', '18.00', 'standard', null, null, true, null, 'DDV-A'));

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

        $this->db->prepare('DELETE FROM products WHERE id = ?')->execute([$this->productId]);
        $this->db->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$this->categoryId]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id = ?')->execute([$this->vatRateId]);
        $this->db->prepare('DELETE FROM partners WHERE id = ?')->execute([$this->partnerId]);
    }

    private function sellerConfig(): EinvoiceConfig
    {
        return new EinvoiceConfig([
            'api_base_url' => 'https://efakturatest.ujp.gov.mk/einvoice_api',
            'send_url' => 'https://efakturatest.ujp.gov.mk/JSONReceiver/api/v1/sales-invoices/send',
            'eujp_id' => null,
            'edb' => null,
            'cert_path' => null,
            'cert_password' => null,
            'cert_serial' => null,
            'seller' => [
                'tin' => '4030995135699',
                'vat_number' => 'МК4030995135699',
                'name' => 'ТЕСТ ДОО СКОПЈЕ',
                'country_code' => 'MK',
                'street' => 'ОРЦЕ НИКОЛОВ',
                'number' => '133',
                'postal_code' => '1000',
                'city' => 'СКОПЈЕ',
            ],
        ]);
    }

    public function test_it_builds_a_payload_matching_the_ujp_schema(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->partnerId, '2026-08-19', '2026-09-18', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '2'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $invoice = $this->invoices->find($invoiceId);
        $buyer = $this->partners->find($this->partnerId);
        $mkd = (new CurrencyRepository())->base();

        $document = $this->builder->build($invoice, $buyer, $mkd, $this->sellerConfig());

        $this->assertSame('100', $document['header']['docType']);
        $this->assertSame('2026-08-19', $document['header']['docDate']);
        $this->assertSame($invoice->number, $document['header']['docNumber']);

        $this->assertSame('4030995135699', $document['seller']['sellerTin']);
        $this->assertSame('4030995135699', $document['buyer']['buyerTin']);
        $this->assertNull($document['buyer']['buyerForeignTin']);

        $this->assertCount(1, $document['docItems']);
        $item = $document['docItems'][0];
        $this->assertSame(2.0, $item['docItemQty']);
        $this->assertSame(2000.0, $item['docItemTotalPriceWoVat']);
        $this->assertSame(360.0, $item['docItemTotalVat']);
        $this->assertSame('DDV-A', $item['docItemTaxIndicator']);

        $this->assertSame(2000.0, $document['docTotals']['docNetAmount']);
        $this->assertSame(360.0, $document['docTotals']['docVatAmount']);
        $this->assertSame(2360.0, $document['docTotals']['docGrossAmount']);

        $this->assertCount(1, $document['vatTotals']);
        $this->assertSame('DDV-A', $document['vatTotals'][0]['vatTaxIndicator']);
        $this->assertSame(18.0, $document['vatTotals'][0]['vatPercent']);
        $this->assertSame(360.0, $document['vatTotals'][0]['vatAmount']);
    }

    public function test_it_refuses_to_build_when_vat_rate_has_no_ujp_mapping(): void
    {
        $this->db->prepare('UPDATE vat_rates SET ujp_tax_indicator_code = NULL WHERE id = ?')->execute([$this->vatRateId]);

        $invoiceId = $this->invoiceService->createInvoice($this->partnerId, '2026-08-19', '2026-09-18', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $invoice = $this->invoices->find($invoiceId);
        $buyer = $this->partners->find($this->partnerId);
        $mkd = (new CurrencyRepository())->base();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/УЈП даночен индикатор/');

        $this->builder->build($invoice, $buyer, $mkd, $this->sellerConfig());
    }
}
