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
use App\Service\PaymentMatchingService;
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

    public function test_it_can_edit_a_draft_invoice_replacing_lines_and_totals(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $this->service->updateInvoice($invoiceId, $this->partnerId, '2026-02-01', '2026-02-28', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '3'],
        ]);

        $invoice = $this->invoices->find($invoiceId);

        $this->assertSame('2026-02-01', $invoice->date);
        $this->assertSame('2026-02-28', $invoice->dueDate);
        $this->assertCount(1, $invoice->lines);
        // 3 * 1000 = 3000 нето, наместо 1000 претходно — старите линии реално се заменети, не додадени
        $this->assertSame('3000.00', $invoice->totalNet);
        $this->assertSame('3540.00', $invoice->totalGross);
    }

    public function test_it_refuses_to_edit_a_paid_or_cancelled_invoice(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;
        $this->service->issue($invoiceId);
        $this->createdEntryIds[] = $this->invoices->find($invoiceId)->journalEntryId;
        $this->service->markPaid($invoiceId);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Само нацрт или издадена/');

        $this->service->updateInvoice($invoiceId, $this->partnerId, '2026-02-01', '2026-02-28', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '5'],
        ]);
    }

    public function test_it_can_edit_an_issued_invoice_when_nothing_is_matched_yet_and_reposts_the_journal(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;
        $this->service->issue($invoiceId);
        $originalEntryId = $this->invoices->find($invoiceId)->journalEntryId;
        $this->createdEntryIds[] = $originalEntryId;

        $this->service->updateInvoice($invoiceId, $this->partnerId, '2026-01-05', '2026-02-04', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '4'],
        ]);

        $invoice = $this->invoices->find($invoiceId);
        $this->createdEntryIds[] = $invoice->journalEntryId;

        // Останува издадена, но со нов journal_entry_id — стариот запис не е избришан (само сторниран).
        $this->assertSame('issued', $invoice->status);
        $this->assertNotSame($originalEntryId, $invoice->journalEntryId);
        // 4 * 1000 = 4000 нето, 18% ддв = 720
        $this->assertSame('4000.00', $invoice->totalNet);
        $this->assertSame('4720.00', $invoice->totalGross);

        // Стариот запис сепак постои и е точно сторниран (дебит/кредит заменети со оригиналот) — не е избришан.
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$originalEntryId]);
        $original = $stmt->fetch();
        $this->assertEquals(1180.00, (float) $original['d']); // 1 * 1000 + 18% = 1180, непроменето

        // Новиот запис е балансиран и одразува 4720
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$invoice->journalEntryId]);
        $new = $stmt->fetch();
        $this->assertEquals(4720.00, (float) $new['d']);
        $this->assertEquals(4720.00, (float) $new['c']);
    }

    public function test_it_refuses_to_edit_an_issued_invoice_once_a_payment_is_matched(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;
        $this->service->issue($invoiceId);
        $this->createdEntryIds[] = $this->invoices->find($invoiceId)->journalEntryId;

        $accounts = new AccountRepository();
        $cashAccount = $accounts->findByCode('221');
        $matching = new PaymentMatchingService();
        $statementId = $matching->createStatement($cashAccount->id, '2026-01-10', 'SMOKE');
        $txId = $matching->addTransaction($statementId, '2026-01-10', 'уплата', null, '500.00', 'in', $this->partnerId, $cashAccount->id);
        $matching->matchToSalesInvoice($txId, $invoiceId, $cashAccount->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/поврзана банкарска трансакција/');

        try {
            $this->service->updateInvoice($invoiceId, $this->partnerId, '2026-02-01', '2026-02-28', [
                ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '5'],
            ]);
        } finally {
            $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM bank_transactions WHERE id = ?)')->execute([$txId]);
            $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM bank_transactions WHERE id = ?)')->execute([$txId]);
            $this->db->prepare('DELETE FROM bank_transactions WHERE id = ?')->execute([$txId]);
            $this->db->prepare('DELETE FROM bank_statements WHERE id = ?')->execute([$statementId]);
        }
    }

    public function test_it_refuses_to_edit_an_invoice_already_sent_as_einvoice(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;
        $this->service->issue($invoiceId);
        $this->createdEntryIds[] = $this->invoices->find($invoiceId)->journalEntryId;
        $this->invoices->recordEinvoiceSent($invoiceId, 'test-euid-123');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/регистрирана кај даночната управа/');

        $this->service->updateInvoice($invoiceId, $this->partnerId, '2026-02-01', '2026-02-28', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '5'],
        ]);
    }
}
