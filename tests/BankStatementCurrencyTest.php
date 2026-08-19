<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\Product;
use App\Domain\Invoicing\ProductCategory;
use App\Repository\AccountRepository;
use App\Repository\BankStatementRepository;
use App\Repository\BankTransactionRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseInvoiceRepository;
use App\Repository\VatRateRepository;
use App\Service\InvoiceService;
use App\Service\PaymentMatchingService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Девизни изводи (POSTING_RULES_ADDENDUM.md §8) — курсот живее на
 * трансакцијата, изводот само го носи currency_id. Секое книжење во ГК
 * останува во MKD преку BankTransaction::amountInBaseCurrency().
 */
class BankStatementCurrencyTest extends TestCase
{
    private PDO $db;
    private BankStatementRepository $statements;
    private BankTransactionRepository $transactions;
    private InvoiceRepository $invoices;
    private PaymentMatchingService $matching;
    private InvoiceService $invoiceService;

    private int $eurCurrencyId;
    private int $customerId;
    private int $vatStandardId;
    private int $productCategoryId;
    private int $productId;
    private int $cashAccountId;
    private int $receivablesForeignId;
    private int $bankFeesAccountId;
    private int $eurStatementId;

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->statements = new BankStatementRepository();
        $this->transactions = new BankTransactionRepository();
        $this->invoices = new InvoiceRepository();
        $purchaseInvoices = new PurchaseInvoiceRepository();
        $this->matching = new PaymentMatchingService($this->statements, $this->transactions, $this->invoices, $purchaseInvoices);
        $this->invoiceService = new InvoiceService($this->invoices);

        $stmt = $this->db->prepare("SELECT id FROM currencies WHERE code = 'EUR'");
        $stmt->execute();
        $this->eurCurrencyId = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare("INSERT INTO partners (name, type, country) VALUES (?, 'both', 'DE')");
        $stmt->execute(['Тест девизен партнер ' . uniqid()]);
        $this->customerId = (int) $this->db->lastInsertId();

        $accounts = new AccountRepository();
        $revenue = $accounts->findByCode('751');
        $vatPayable = $accounts->findByCode('260');
        $vatReceivable = $accounts->findByCode('160');
        $this->cashAccountId = $accounts->findByCode('100')->id;
        $this->receivablesForeignId = $accounts->findByCode('1201')->id;
        $this->bankFeesAccountId = $accounts->findByCode('447')->id;

        $vatRateRepo = new VatRateRepository();
        $this->vatStandardId = $vatRateRepo->create(new VatRate('Тест девизна стандардна', '18.00', 'standard', $vatPayable->id, $vatReceivable->id));

        $productCategoryRepo = new ProductCategoryRepository();
        $this->productCategoryId = $productCategoryRepo->create(new ProductCategory('Тест девизна категорија ' . uniqid(), $revenue->id, $this->vatStandardId));

        $productRepo = new ProductRepository();
        $this->productId = $productRepo->create(new Product('Тест девизен производ', $this->productCategoryId, '100.00'));

        $this->eurStatementId = $this->matching->createStatement($this->cashAccountId, '2026-04-01', 'EUR-ИЗВОД', '0.00', $this->eurCurrencyId);
    }

    protected function tearDown(): void
    {
        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->eurStatementId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM bank_transactions WHERE bank_statement_id = ?)')->execute([$this->eurStatementId]);
        $this->db->prepare('DELETE FROM bank_transactions WHERE bank_statement_id = ?')->execute([$this->eurStatementId]);
        $this->db->prepare('DELETE FROM bank_statements WHERE id = ?')->execute([$this->eurStatementId]);

        $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT journal_entry_id FROM invoices WHERE partner_id = ?)')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM journal_entries WHERE id IN (SELECT journal_entry_id FROM invoices WHERE partner_id = ?)')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM invoices WHERE partner_id = ?')->execute([$this->customerId]);
        $this->db->prepare('DELETE FROM products WHERE id = ?')->execute([$this->productId]);
        $this->db->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$this->productCategoryId]);
        $this->db->prepare('DELETE FROM vat_rates WHERE id = ?')->execute([$this->vatStandardId]);
        $this->db->prepare('DELETE FROM partners WHERE id = ?')->execute([$this->customerId]);
    }

    public function test_manual_transaction_on_a_eur_statement_posts_the_mkd_equivalent(): void
    {
        // 100 EUR банкарска провизија, курс 61.50 → 6150.00 MKD во ГК.
        $transactionId = $this->matching->addTransaction($this->eurStatementId, '2026-04-05', 'Провизија', null, '100.00', 'out', null, $this->bankFeesAccountId, '61.500000');
        $this->matching->postManual($transactionId);

        $transaction = $this->transactions->find($transactionId);
        $this->assertSame('100.00', $transaction->amount, 'суровиот износ останува во EUR, не се препишува во MKD');
        $this->assertSame('6150.00', $transaction->amountInBaseCurrency());

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = ?');
        $stmt->execute([$transaction->journalEntryId]);
        $sums = $stmt->fetch();
        $this->assertEquals(6150.00, (float) $sums['d']);
        $this->assertEquals(6150.00, (float) $sums['c']);
    }

    public function test_denarski_statement_forces_exchange_rate_to_one_regardless_of_input(): void
    {
        $mkdStatementId = $this->matching->createStatement($this->cashAccountId, '2026-04-01', 'MKD-ИЗВОД');

        // Обид да се проследи курс различен од 1 на денарски извод — мора да се игнорира.
        $transactionId = $this->matching->addTransaction($mkdStatementId, '2026-04-05', 'Провизија', null, '100.00', 'out', null, $this->bankFeesAccountId, '61.500000');
        $transaction = $this->transactions->find($transactionId);

        $this->assertSame('1.000000', $transaction->exchangeRate);
        $this->assertSame('100.00', $transaction->amountInBaseCurrency());

        $this->db->prepare('DELETE FROM bank_transactions WHERE bank_statement_id = ?')->execute([$mkdStatementId]);
        $this->db->prepare('DELETE FROM bank_statements WHERE id = ?')->execute([$mkdStatementId]);
    }

    public function test_it_rejects_a_non_positive_exchange_rate_on_a_foreign_statement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->matching->addTransaction($this->eurStatementId, '2026-04-05', 'Провизија', null, '100.00', 'out', null, $this->bankFeesAccountId, '0');
    }

    public function test_matching_a_eur_transaction_to_a_eur_invoice_at_the_same_rate_closes_it_without_fx_difference(): void
    {
        // 1 производ по 100.00 EUR, 18% ДДВ = 118.00 EUR бруто; курс 61.50 → 7257.00 MKD.
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-04-01', '2026-04-30', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ], $this->eurCurrencyId, '61.500000');
        $this->invoiceService->issue($invoiceId);

        // Уплата од 118 EUR примена на ИСТ курс како фактурата — нема курсна разлика.
        $transactionId = $this->matching->addTransaction($this->eurStatementId, '2026-04-10', 'Уплата купувач', null, '118.00', 'in', $this->customerId, $this->receivablesForeignId, '61.500000');
        $this->matching->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesForeignId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(jl.debit), 0) FROM journal_lines jl
             JOIN accounts a ON a.id = jl.account_id
             WHERE jl.journal_entry_id = ? AND a.code = '4750'"
        );
        $stmt->execute([$transaction->journalEntryId]);
        $this->assertEquals(0.00, (float) $stmt->fetchColumn(), 'ист курс на фактура и уплата — нема реализирана курсна разлика');
    }

    public function test_partial_eur_payment_leaves_the_correct_mkd_outstanding_balance(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-04-01', '2026-04-30', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ], $this->eurCurrencyId, '61.500000');
        $this->invoiceService->issue($invoiceId);
        // Booked AR = 7257.00 MKD. Делумна уплата: 50 EUR на курс 62.0 → 3100.00 MKD.

        $transactionId = $this->matching->addTransaction($this->eurStatementId, '2026-04-10', 'Делумна уплата', null, '50.00', 'in', $this->customerId, $this->receivablesForeignId, '62.000000');
        $this->matching->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesForeignId);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('issued', $invoice->status);

        $matched = $this->transactions->matchedAmountForInvoice('sales', $invoiceId);
        $this->assertSame('3100.00', $matched);
    }

    public function test_matching_at_a_different_bank_rate_with_fx_close_posts_the_difference_from_the_bank_rate(): void
    {
        $invoiceId = $this->invoiceService->createInvoice($this->customerId, '2026-04-01', '2026-04-30', [
            ['type' => 'product', 'item_id' => $this->productId, 'quantity' => '1'],
        ], $this->eurCurrencyId, '61.500000');
        $this->invoiceService->issue($invoiceId);
        // Booked AR = 7257.00 MKD (118 EUR × 61.5). Уплата 118 EUR на курс 61.0 → 7198.00 MKD → 59.00 MKD реализирана загуба.

        $transactionId = $this->matching->addTransaction($this->eurStatementId, '2026-04-10', 'Уплата FX', null, '118.00', 'in', $this->customerId, $this->receivablesForeignId, '61.000000');
        $this->matching->matchToSalesInvoice($transactionId, $invoiceId, $this->receivablesForeignId, true);

        $invoice = $this->invoices->find($invoiceId);
        $this->assertSame('paid', $invoice->status);

        $transaction = $this->transactions->find($transactionId);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(jl.debit), 0) FROM journal_lines jl
             JOIN accounts a ON a.id = jl.account_id
             WHERE jl.journal_entry_id = ? AND a.code = '4750'"
        );
        $stmt->execute([$transaction->journalEntryId]);
        $this->assertEquals(59.00, (float) $stmt->fetchColumn());
    }
}
