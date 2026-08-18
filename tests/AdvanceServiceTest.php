<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\ExpenseCategory;
use App\Domain\Invoicing\Product;
use App\Domain\Invoicing\ProductCategory;
use App\Repository\AccountRepository;
use App\Repository\AdvanceApplicationRepository;
use App\Repository\BankStatementRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\ExpenseCategoryRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\AdvanceService;
use App\Service\InvoiceService;
use App\Service\PaymentMatchingService;
use App\Service\PurchaseInvoiceService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class AdvanceServiceTest extends TestCase
{
    private PDO $db;
    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private AdvanceApplicationRepository $applications;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private PaymentMatchingService $paymentMatching;
    private AdvanceService $service;
    private InvoiceService $invoiceService;
    private PurchaseInvoiceService $purchaseInvoiceService;

    private int $customerId;
    private int $supplierId;
    private int $vatStandardId;
    private int $productId;
    private int $productCategoryId;
    private int $expenseCategoryId;
    private int $cashAccountId;
    private int $receivablesAccountId;
    private int $payablesAccountId;
    private int $advanceReceivedAccountId;
    private int $advanceGivenAccountId;
    private int $statementId;

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->statements = new BankStatementRepository();
        $this->transactions = new BankTransactionRepository();
        $this->applications = new AdvanceApplicationRepository();
        $this->invoices = new InvoiceRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
        $this->paymentMatching = new PaymentMatchingService($this->statements, $this->transactions, $this->invoices, $this->purchaseInvoices);
        $this->service = new AdvanceService($this->applications, $this->transactions, $this->invoices, $this->purchaseInvoices);
        $this->invoiceService = new InvoiceService($this->invoices);
        $this->purchaseInvoiceService = new PurchaseInvoiceService($this->purchaseInvoices);

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'customer', 'MK')");
        $stmt->execute(['Тест купувач аванс ' . uniqid()]);
        $this->customerId = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'supplier', 'MK')");
        $stmt->execute(['Тест добавувач аванс ' . uniqid()]);
        $this->supplierId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');
        $vatPayable = $accounts->findByCode('260');
        $vatReceivable = $accounts->findByCode('160');
        $expenseAccount = $accounts->findByCode('419');
        $this->cashAccountId = $accounts->findByCode('100')->id;
        $this->receivablesAccountId = $accounts->findByCode('1200')->id;
        $this->payablesAccountId = $accounts->findByCode('2200')->id;
        $this->advanceReceivedAccountId = $accounts->findByCode('2230')->id;
        $this->advanceGivenAccountId = $accounts->findByCode('3700')->id;

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест стандардна аванс', '18.00', 'standard', $vatPayable->id, $vatReceivable->id));

        $productCategoryRepo = new ProductCategoryRepository();
        $this->productCategoryId = $productCategoryRepo->create(new ProductCategory('Тест категорија аванс ' . uniqid(), $revenue->id, $this->vatStandardId));

        $productRepo = new ProductRepository();
        $this->productId = $productRepo->create(new Product('Тест производ аванс', $this->productCategoryId, '1000.00'));

        $expenseCategoryRepo = new ExpenseCategoryRepository();
        $this->expenseCategoryId = $expenseCategoryRepo->create(new ExpenseCategory('Тест категорија трошок аванс ' . uniqid(), $expenseAccount->id, null, 'full'));

        $this->statementId = $this->paymentMatching->createStatement($this->cashAccountId, '2026-03-01', 'ИЗВОД-АВАНС');
    }

    protected function tearDown(): void
    {
        $this->db->prepare('DELETE FROM advance_applications WHERE bank_transaction_id IN (SELECT id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->statementId]);

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

    private function receiveAdvance(string $amount): int
    {
        $transactionId = $this->paymentMatching->addTransaction($this->statementId, '2026-03-01', 'Примен аванс', null, $amount, 'in', $this->customerId, $this->advanceReceivedAccountId);
        $this->paymentMatching->postManual($transactionId);

        return $transactionId;
    }

    private function giveAdvance(string $amount): int
    {
        $transactionId = $this->paymentMatching->addTransaction($this->statementId, '2026-03-01', 'Даден аванс', null, $amount, 'out', $this->supplierId, $this->advanceGivenAccountId);
        $this->paymentMatching->postManual($transactionId);

        return $transactionId;
    }

    public function test_partial_application_leaves_invoice_open_with_reduced_outstanding(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-05', '2026-04-04', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);
        // 1000 нето + 180 ддв = 1180 бруто

        $transactionId = $this->receiveAdvance('1180.00');
        $this->service->applyToSalesInvoice($transactionId, $invoiceId, '700.00', $this->receivablesAccountId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('issued', $invoice->status);

        $outstanding = $this->service->outstandingForSalesInvoice($invoiceId, $invoice->totalGross);
        $this->assertSame('480.00', $outstanding);
    }

    public function test_mixed_advance_and_bank_payment_closes_invoice_without_overpaying(): void
    {
        // Ова е тестот што конкретно ја докажува поправката во
        // PaymentMatchingService — банкарскиот дел мора да го земе предвид
        // веќе-применетиот аванс, инаку би дозволил надплата.
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-05', '2026-04-04', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);
        // 1180 бруто вкупно

        $advanceTransactionId = $this->receiveAdvance('700.00');
        $this->service->applyToSalesInvoice($advanceTransactionId, $invoiceId, '700.00', $this->receivablesAccountId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('issued', $invoice->status);

        $paymentTransactionId = $this->paymentMatching->addTransaction($this->statementId, '2026-03-10', 'Доплата', null, '480.00', 'in', $this->customerId, $this->receivablesAccountId);
        $this->paymentMatching->matchToSalesInvoice($paymentTransactionId, $invoiceId, $this->receivablesAccountId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status, 'фактурата треба да е платена откако аванс+уплата го покриваат целиот износ');

        // Обид за уште една уплата над веќе-платеното мора да падне — доказ дека комбинираното преостанато е точно 0, не се дозволува надплата.
        $overpayTransactionId = $this->paymentMatching->addTransaction($this->statementId, '2026-03-11', 'Погрешна дополнителна уплата', null, '50.00', 'in', $this->customerId, $this->cashAccountId);
        $this->expectException(InvalidArgumentException::class);
        $this->paymentMatching->matchToSalesInvoice($overpayTransactionId, $invoiceId, $this->receivablesAccountId);
    }

    public function test_it_rejects_applying_more_than_the_advance_remaining(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-05', '2026-04-04', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        $transactionId = $this->receiveAdvance('500.00');

        $this->expectException(InvalidArgumentException::class);
        $this->service->applyToSalesInvoice($transactionId, $invoiceId, '600.00', $this->receivablesAccountId);
    }

    public function test_it_rejects_applying_a_transaction_not_on_an_advance_account(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-05', '2026-04-04', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        // Обична трансакција книжена директно на банкарска сметка, не на аванс-конто.
        $transactionId = $this->paymentMatching->addTransaction($this->statementId, '2026-03-01', 'Не е аванс', null, '500.00', 'in', $this->customerId, $this->cashAccountId);
        $this->paymentMatching->postManual($transactionId);

        $this->expectException(InvalidArgumentException::class);
        $this->service->applyToSalesInvoice($transactionId, $invoiceId, '500.00', $this->receivablesAccountId);
    }

    public function test_given_advance_applies_to_purchase_invoice_and_marks_it_paid(): void
    {
        $purchaseInvoiceId = $this->purchaseInvoiceService->createPurchaseInvoice($this->supplierId, 'SUP-ADV-001', '2026-03-05', '2026-04-04', [
            ['category_id' => $this->expenseCategoryId, 'quantity' => '1', 'unit_price' => '1000.00', 'vat_rate_id' => $this->vatStandardId],
        ]);
        $this->purchaseInvoiceService->post($purchaseInvoiceId);
        // 1180 бруто

        $transactionId = $this->giveAdvance('1180.00');
        $this->service->applyToPurchaseInvoice($transactionId, $purchaseInvoiceId, '1180.00', $this->payablesAccountId);

        $invoice = $this->purchaseInvoices->find($purchaseInvoiceId);
        $this->assertSame('paid', $invoice->status);
    }
}
