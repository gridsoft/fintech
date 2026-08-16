<?php

namespace Tests;

use App\Core\Database;
use App\Repository\InvoiceRepository;
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
    private array $createdInvoiceIds = [];
    private array $createdEntryIds = [];

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->invoices = new InvoiceRepository();
        $this->service = new InvoiceService($this->invoices);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type) VALUES (?, 'customer')");
        $stmt->execute(['Тест партнер ' . uniqid()]);
        $this->partnerId = (int) $this->db->lastInsertId();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdInvoiceIds as $id) {
            $this->db->prepare('DELETE FROM invoices WHERE id = ?')->execute([$id]);
        }

        foreach ($this->createdEntryIds as $id) {
            $this->db->prepare('DELETE FROM journal_entries WHERE id = ?')->execute([$id]);
        }

        $this->db->prepare('DELETE FROM partners WHERE id = ?')->execute([$this->partnerId]);
    }

    public function test_it_calculates_totals_from_lines_with_vat(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['description' => 'Услуга А', 'quantity' => '2', 'unit_price' => '1000.00', 'vat_rate' => '18'],
            ['description' => 'Услуга Б', 'quantity' => '1', 'unit_price' => '500.00', 'vat_rate' => '5'],
        ]);
        $this->createdInvoiceIds[] = $invoiceId;

        $invoice = $this->invoices->find($invoiceId);

        // нето: 2*1000 + 1*500 = 2500.00
        $this->assertSame('2500.00', $invoice->totalNet);
        // ддв: 2000*0.18 + 500*0.05 = 360 + 25 = 385.00
        $this->assertSame('385.00', $invoice->totalVat);
        $this->assertSame('2885.00', $invoice->totalGross);
        $this->assertSame('draft', $invoice->status);
    }

    public function test_issuing_generates_a_balanced_journal_entry(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['description' => 'Услуга', 'quantity' => '1', 'unit_price' => '1000.00', 'vat_rate' => '18'],
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

        // AR линијата мора да е таговирана со партнерот
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM journal_lines WHERE journal_entry_id = ? AND partner_id = ?'
        );
        $stmt->execute([$invoice->journalEntryId, $this->partnerId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function test_it_cannot_issue_the_same_invoice_twice(): void
    {
        $invoiceId = $this->service->createInvoice($this->partnerId, '2026-01-01', '2026-01-31', [
            ['description' => 'Услуга', 'quantity' => '1', 'unit_price' => '100.00', 'vat_rate' => '0'],
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
