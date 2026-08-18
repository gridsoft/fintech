<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\ExpenseCategory;
use App\Domain\Invoicing\Product;
use App\Domain\Invoicing\ProductCategory;
use App\Repository\AccountRepository;
use App\Repository\BankStatementRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\InvoiceService;
use App\Service\PaymentMatchingService;
use App\Service\PurchaseInvoiceService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaymentMatchingServiceTest extends TestCase
{
    private PDO $db;
    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PaymentMatchingService $service;
    private InvoiceService $invoiceService;
    private PurchaseInvoiceService $purchaseInvoiceService;

    private int $customerId;
    private int $supplierId;
    private int $vatStandardId;
    private int $productCategoryId;
    private int $productId;
    private int $expenseCategoryId;
    private int $cashAccountId;
    private int $receivablesAccountId;
    private int $payablesAccountId;
    private int $bankFeesAccountId;
    private int $statementId;

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->statements = new BankStatementRepository();
        $this->transactions = new BankTransactionRepository();
        $this->invoices = new InvoiceRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
        $this->service = new PaymentMatchingService($this->statements, $this->transactions, $this->invoices, $this->purchaseInvoices);
        $this->invoiceService = new InvoiceService($this->invoices);
        $this->purchaseInvoiceService = new PurchaseInvoiceService($this->purchaseInvoices);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'customer', 'MK')");
        $stmt->execute(['Тест купувач ' . uniqid()]);
        $this->customerId = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'supplier', 'MK')");
        $stmt->execute(['Тест добавувач ' . uniqid()]);
        $this->supplierId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');
        $vatPayable = $accounts->findByCode('260');
        $vatReceivable = $accounts->findByCode('160');
        $expenseAccount = $accounts->findByCode('419');
        $this->cashAccountId = $accounts->findByCode('100')->id;
        $this->receivablesAccountId = $accounts->findByCode('1200')->id;
        $this->payablesAccountId = $accounts->findByCode('2200')->id;
        $this->bankFeesAccountId = $accounts->findByCode('447')->id;

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест стандардна', '18.00', 'standard', $vatPayable->id, $vatReceivable->id));

        $productCategoryRepo = new ProductCategoryRepository();
        $this->productCategoryId = $productCategoryRepo->create(new ProductCategory('Тест категорија ' . uniqid(), $revenue->id, $this->vatStandardId));

        $productRepo = new ProductRepository();
        $this->productId = $productRepo->create(new Product('Тест производ', $this->productCategoryId, '1000.00'));

        $expenseCategoryRepo = new ExpenseCategoryRepository();
        $this->expenseCategoryId = $expenseCategoryRepo->create(new ExpenseCategory('Тест категорија трошок ' . uniqid(), $expenseAccount->id, null, 'full'));

        $this->statementId = $this->service->createStatement($this->cashAccountId, '2026-01-15', 'ИЗВОД-ТЕСТ');
    }

    protected function tearDown(): void
    {
        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->statementId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->statementId]);

        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM invoices WHERE partner_id = ?)')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM invoices WHERE partner_id = ?)')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id = ?)')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id = ?)')->execute([$this->supplierId]);

        $this->db->prepare('DELETE FROM bank_transactions WHERE bank_statement_id = ?')->execute([$this->statementId]);
        $this->db->prepare('DELETE FROM bank_statements WHERE id = ?')->execute([$this->statementId]);
        $this->db->prepare('DELETE FROM invoices WHERE partner_id = ?')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM purchase_invoices WHERE partner_id = ?')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM products WHERE id = ?')->execute([$this->productId]);
        $this->db->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$this->productCategoryId]);
        $this->db->prepare('DELETE FROM expense_categories WHERE id = ?')->execute([$this->expenseCategoryId]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id = ?')->execute([$this->vatStandardId]);
        $this->db->prepare('DELETE FROM partners WHERE id IN (?, ?)')->execute([$this->customerId, $this->supplierId]);
    }

    public function test_full_payment_matches_sales_invoice_and_marks_it_paid(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);
        // 1000 нето + 180 ддв = 1180 бруто

        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Уплата купувач', null, '1180.00', 'in', $this->customerId, $this->receivablesAccountId);
        $this->service->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesAccountId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $this->assertSame('matched', $transaction->matchedStatus);
        $this->assertNotNull($transaction->journalEntryId);

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$transaction->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(1180.00, (float) $sums['d']);
        $this->assertEquals(1180.00, (float) $sums['c']);
    }

    public function test_partial_payment_leaves_invoice_open_with_reduced_outstanding_balance(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);
        // 1180 бруто вкупно

        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Делумна уплата', null, '700.00', 'in', $this->customerId, $this->receivablesAccountId);
        $this->service->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesAccountId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('issued', $invoice->status, 'фактурата останува отворена, не е целосно платена');

        $outstanding = $this->transactions->matchedAmountForInvoice('sales', $invoiceId);
        $this->assertSame('700.00', $outstanding);
    }

    public function test_it_rejects_overpayment_beyond_outstanding_balance(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Преголема уплата', null, '5000.00', 'in', $this->customerId, $this->receivablesAccountId);

        $this->expectException(InvalidArgumentException::class);
        $this->service->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesAccountId);
    }

    public function test_it_rejects_matching_an_out_transaction_to_a_sales_invoice(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Погрешна насока', null, '1180.00', 'out', $this->customerId, $this->receivablesAccountId);

        $this->expectException(InvalidArgumentException::class);
        $this->service->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesAccountId);
    }

    public function test_full_payment_matches_purchase_invoice_and_marks_it_paid(): void
    {
        $purchaseInvoiceId = $this->purchaseInvoiceService->createPurchaseInvoice($this->supplierId, 'SUP-PM-001', '2026-01-01', '2026-01-31', [
            ['category_id' => $this->expenseCategoryId, 'quantity' => '1', 'unit_price' => '1000.00', 'vat_rate_id' => $this->vatStandardId],
        ]);
        $this->purchaseInvoiceService->post($purchaseInvoiceId);
        // 1180 бруто

        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Исплата добавувач', null, '1180.00', 'out', $this->supplierId, $this->payablesAccountId);
        $this->service->matchToPurchaseInvoice($transactionId, $purchaseInvoiceId, $this->payablesAccountId);

        $invoice = $this->purchaseInvoices->find($purchaseInvoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$transaction->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(1180.00, (float) $sums['d']);
        $this->assertEquals(1180.00, (float) $sums['c']);
    }

    public function test_it_cannot_rematch_an_already_matched_transaction(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Уплата', null, '1180.00', 'in', $this->customerId, $this->receivablesAccountId);
        $this->service->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesAccountId);

        $this->expectException(RuntimeException::class);
        $this->service->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesAccountId);
    }

    public function test_manual_transaction_without_invoice_posts_immediately(): void
    {
        $transactionId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Банкарски провизии', 'REF-1', '50.00', 'out', null, $this->bankFeesAccountId);
        $this->service->postManual($transactionId);

        $transaction = $this->transactions->find($transactionId);
        $this->assertSame('matched', $transaction->matchedStatus);
        $this->assertNull($transaction->invoiceType);
        $this->assertNotNull($transaction->journalEntryId);
        $this->assertSame($this->bankFeesAccountId, $transaction->glAccountId);

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$transaction->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(50.00, (float) $sums['d']);
        $this->assertEquals(50.00, (float) $sums['c']);
    }

    public function test_balance_after_accumulates_across_transactions(): void
    {
        $this->db->prepare('UPDATE bank_statements SET opening_balance = ? WHERE id = ?')->execute(['1000.00', $this->statementId]);

        $firstId = $this->service->addTransaction($this->statementId, '2026-01-16', 'Прилив', null, '300.00', 'in', null, $this->bankFeesAccountId);
        $secondId = $this->service->addTransaction($this->statementId, '2026-01-17', 'Одлив', null, '120.00', 'out', null, $this->bankFeesAccountId);

        $first = $this->transactions->find($firstId);
        $second = $this->transactions->find($secondId);

        $this->assertSame('1300.00', $first->balanceAfter);
        $this->assertSame('1180.00', $second->balanceAfter);
    }

    public function test_add_matched_transaction_posts_and_pays_in_one_call(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-01-01', '2026-01-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        $transactionId = $this->service->addMatchedTransaction($this->statementId, '2026-01-16', 'Уплата купувач', null, '1180.00', 'in', $this->customerId, $this->receivablesAccountId, 'sales', $invoiceId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $this->assertSame('matched', $transaction->matchedStatus);
        $this->assertNotNull($transaction->journalEntryId);
    }
}
