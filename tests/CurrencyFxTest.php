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
use App\Repository\FxRevaluationRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\FxRevaluationService;
use App\Service\InvoiceService;
use App\Service\PaymentMatchingService;
use App\Service\PurchaseInvoiceService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CurrencyFxTest extends TestCase
{
    private PDO $db;
    private InvoiceRepository $invoices;
    private PurchaseInvoiceRepository $purchaseInvoices;
    private InvoiceService $invoiceService;
    private PurchaseInvoiceService $purchaseInvoiceService;
    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private PaymentMatchingService $matching;
    private FxRevaluationRepository $revaluations;
    private FxRevaluationService $revaluationService;

    private int $eurCurrencyId;
    private int $customerId;
    private int $supplierId;
    private int $vatStandardId;
    private int $productCategoryId;
    private int $productId;
    private int $expenseCategoryId;
    private int $cashAccountId;
    private int $receivablesForeignId;
    private int $payablesForeignId;
    private int $statementId;

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->invoices = new InvoiceRepository();
        $this->purchaseInvoices = new PurchaseInvoiceRepository();
        $this->invoiceService = new InvoiceService($this->invoices);
        $this->purchaseInvoiceService = new PurchaseInvoiceService($this->purchaseInvoices);
        $this->statements = new BankStatementRepository();
        $this->transactions = new BankTransactionRepository();
        $this->matching = new PaymentMatchingService($this->statements, $this->transactions, $this->invoices, $this->purchaseInvoices);
        $this->revaluations = new FxRevaluationRepository();
        $this->revaluationService = new FxRevaluationService();

        $stmt = $this->db->prepare("SELECT id FROM currencies WHERE code = 'EUR'");
        $stmt->execute();
        $this->eurCurrencyId = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'both', 'DE')");
        $stmt->execute(['Тест FX партнер ' . uniqid()]);
        $this->customerId = (int) $this->db->lastInsertId();
        $this->supplierId = $this->customerId;

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');
        $vatPayable = $accounts->findByCode('260');
        $vatReceivable = $accounts->findByCode('160');
        $expenseAccount = $accounts->findByCode('419');
        $this->cashAccountId = $accounts->findByCode('100')->id;
        $this->receivablesForeignId = $accounts->findByCode('1201')->id;
        $this->payablesForeignId = $accounts->findByCode('2201')->id;

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест FX стандардна', '18.00', 'standard', $vatPayable->id, $vatReceivable->id));

        $productCategoryRepo = new ProductCategoryRepository();
        $this->productCategoryId = $productCategoryRepo->create(new ProductCategory('Тест FX категорија ' . uniqid(), $revenue->id, $this->vatStandardId));

        $productRepo = new ProductRepository();
        $this->productId = $productRepo->create(new Product('Тест FX производ', $this->productCategoryId, '100.00'));

        $expenseCategoryRepo = new ExpenseCategoryRepository();
        $this->expenseCategoryId = $expenseCategoryRepo->create(new ExpenseCategory('Тест FX категорија трошок ' . uniqid(), $expenseAccount->id, null, 'full'));

        $this->statementId = $this->matching->createStatement($this->cashAccountId, '2026-03-15', 'FX-ИЗВОД');
    }

    protected function tearDown(): void
    {
        $this->db->prepare('DELETE FROM fx_revaluation_lines WHERE fx_revaluation_id IN (SELECT id FROM fx_revaluations WHERE currency_id = ?)')->execute([$this->eurCurrencyId]);
        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM fx_revaluations WHERE currency_id = ?)')->execute([$this->eurCurrencyId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM fx_revaluations WHERE currency_id = ?)')->execute([$this->eurCurrencyId]);
        $this->db->prepare('DELETE FROM fx_revaluations WHERE currency_id = ?')->execute([$this->eurCurrencyId]);

        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->statementId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->statementId]);
        $this->db->prepare('DELETE FROM bank_transactions WHERE bank_statement_id = ?')->execute([$this->statementId]);
        $this->db->prepare('DELETE FROM bank_statements WHERE id = ?')->execute([$this->statementId]);

        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM invoices WHERE partner_id = ?)')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM invoices WHERE partner_id = ?)')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id = ?)')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM purchase_invoices WHERE partner_id = ?)')->execute([$this->supplierId]);

        $this->db->prepare('DELETE FROM invoices WHERE partner_id = ?')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM purchase_invoices WHERE partner_id = ?')->execute([$this->supplierId]);
        $this->db->prepare('DELETE FROM products WHERE id = ?')->execute([$this->productId]);
        $this->db->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$this->productCategoryId]);
        $this->db->prepare('DELETE FROM expense_categories WHERE id = ?')->execute([$this->expenseCategoryId]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id = ?')->execute([$this->vatStandardId]);
        $this->db->prepare('DELETE FROM partners WHERE id = ?')->execute([$this->customerId]);
    }

    public function test_issuing_a_foreign_currency_sales_invoice_posts_the_mkd_equivalent(): void
    {
        // 1 производ по 100.00 EUR, 18% ДДВ = 118.00 EUR бруто; курс 61.50 → 7257.00 MKD
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-01', '2026-03-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ], $this->eurCurrencyId, '61.500000');
        $this->invoiceService->issue($invoiceId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('118.00', $invoice->totalGross);
        $this->assertSame('7257.00', $invoice->grossInBaseCurrency());

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$invoice->journalEntryId]);
        $sums = $stmt->fetch();

        $this->assertEquals(7257.00, (float) $sums['d']);
        $this->assertEquals(7257.00, (float) $sums['c']);
    }

    public function test_posting_a_foreign_currency_purchase_invoice_posts_the_mkd_equivalent(): void
    {
        $invoiceId = $this->purchaseInvoiceService->createPurchaseInvoice($this->supplierId, 'SUP-FX-001', '2026-03-01', '2026-03-31', [
            ['category_id' => $this->expenseCategoryId, 'quantity' => '1', 'unit_price' => '200.00', 'vat_rate_id' => $this->vatStandardId],
        ], $this->eurCurrencyId, '61.500000');
        $this->purchaseInvoiceService->post($invoiceId);

        $invoice = $this->purchaseInvoices->find($invoiceId);
        // 200 нето + 36 ддв = 236.00 EUR бруто × 61.5 = 14514.00 MKD
        $this->assertSame('236.00', $invoice->totalGross);
        $this->assertSame('14514.00', $invoice->grossInBaseCurrency());

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$invoice->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(14514.00, (float) $sums['d']);
        $this->assertEquals(14514.00, (float) $sums['c']);
    }

    public function test_closing_a_foreign_sales_invoice_with_lower_settlement_posts_realized_fx_loss(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-01', '2026-03-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ], $this->eurCurrencyId, '61.500000');
        $this->invoiceService->issue($invoiceId);
        // Booked AR = 7257.00 MKD (118 EUR × 61.5). Bank actually receives 7200.00 MKD → 57.00 MKD realized loss.

        $transactionId = $this->matching->addTransaction($this->statementId, '2026-03-20', 'Уплата FX', null, '7200.00', 'in', $this->customerId, $this->receivablesForeignId);
        $this->matching->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesForeignId, true);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$transaction->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(7257.00, (float) $sums['d']);
        $this->assertEquals(7257.00, (float) $sums['c']);

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(jl.debit), 0) FROM journal_lines jl
             JOIN accounts a ON a.id = jl.account_id
             WHERE jl.journal_entry_id = ? AND a.code = '4750'"
        );
        $stmt->execute([$transaction->journalEntryId]);
        $this->assertEquals(57.00, (float) $stmt->fetchColumn());
    }

    public function test_closing_a_foreign_purchase_invoice_with_higher_settlement_posts_realized_fx_loss(): void
    {
        $invoiceId = $this->purchaseInvoiceService->createPurchaseInvoice($this->supplierId, 'SUP-FX-002', '2026-03-01', '2026-03-31', [
            ['category_id' => $this->expenseCategoryId, 'quantity' => '1', 'unit_price' => '200.00', 'vat_rate_id' => $this->vatStandardId],
        ], $this->eurCurrencyId, '61.500000');
        $this->purchaseInvoiceService->post($invoiceId);
        // Booked AP = 14514.00 MKD. Paid 14600.00 MKD → paid MORE than booked → 86.00 MKD realized loss.

        $transactionId = $this->matching->addTransaction($this->statementId, '2026-03-20', 'Исплата FX', null, '14600.00', 'out', $this->supplierId, $this->payablesForeignId);
        $this->matching->matchToPurchaseInvoice($transactionId, $invoiceId, $this->payablesForeignId, true);

        $invoice = $this->purchaseInvoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$transaction->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(14600.00, (float) $sums['d']);
        $this->assertEquals(14600.00, (float) $sums['c']);

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(jl.debit), 0) FROM journal_lines jl
             JOIN accounts a ON a.id = jl.account_id
             WHERE jl.journal_entry_id = ? AND a.code = '4750'"
        );
        $stmt->execute([$transaction->journalEntryId]);
        $this->assertEquals(86.00, (float) $stmt->fetchColumn());
    }

    public function test_it_rejects_fx_closing_for_a_domestic_currency_invoice(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-01', '2026-03-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ]);
        $this->invoiceService->issue($invoiceId);

        $transactionId = $this->matching->addTransaction($this->statementId, '2026-03-20', 'Уплата', null, '100.00', 'in', $this->customerId, $this->receivablesForeignId);

        $this->expectException(InvalidArgumentException::class);
        $this->matching->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesForeignId, true);
    }

    public function test_revaluation_books_unrealized_gain_and_second_run_does_not_double_count(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-03-01', '2026-03-31', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ], $this->eurCurrencyId, '61.500000');
        $this->invoiceService->issue($invoiceId);
        // Booked AR = 7257.00 MKD (118 EUR at 61.5). Revalue at 62.0 → 118 × 62.0 = 7316.00 → +59.00 gain.

        $firstId = $this->revaluationService->revalue('2026-03-31', $this->eurCurrencyId, '62.000000');

        $stmt = $this->db->prepare('SELECT journal_entry_id FROM fx_revaluations WHERE id = ?');
        $stmt->execute([$firstId]);
        $journalEntryId = $stmt->fetchColumn();
        $this->assertNotFalse($journalEntryId);

        // Проверка на СПЕЦИФИЧНАТА линија за оваа фактура, не на збирот на записот —
        // devDB не е изолирана, може да содржи и други отворени EUR фактури.
        $stmt = $this->db->prepare('SELECT difference FROM fx_revaluation_lines WHERE fx_revaluation_id = ? AND invoice_type = ? AND invoice_id = ?');
        $stmt->execute([$firstId, 'sales', $invoiceId]);
        $this->assertEquals(59.00, (float) $stmt->fetchColumn());

        // Втора превалоризација на ИСТ курс — нема разлика во однос на последната, не смее да книжи ништо.
        $this->expectException(RuntimeException::class);
        $this->revaluationService->revalue('2026-04-30', $this->eurCurrencyId, '62.000000');
    }
}
