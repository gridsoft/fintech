<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\Invoice;
use App\Domain\Invoicing\InvoiceLine;
use PDO;

class InvoiceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Invoice[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM invoices ORDER BY invoice_date DESC, id DESC');

        return array_map([Invoice::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Издадени фактури на еден партнер, сè уште не целосно платени — единствените што можат да се поврзат со уплата. */
    public function openForMatchingByPartner(int $partnerId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE status = 'issued' AND partner_id = ? ORDER BY invoice_date ASC, id ASC");
        $stmt->execute([$partnerId]);

        return array_map([Invoice::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Invoice
    {
        $stmt = $this->db->prepare('SELECT * FROM invoices WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $invoice = Invoice::fromRow($row);
        $invoice->lines = $this->linesForInvoice($id);

        return $invoice;
    }

    /** @return InvoiceLine[] */
    public function linesForInvoice(int $invoiceId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoice_lines WHERE invoice_id = ? ORDER BY id ASC');
        $stmt->execute([$invoiceId]);

        return array_map([InvoiceLine::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Издадени/платени фактури — за AR-остарување и вкупни отворени побарувања (контролна табла). */
    public function openIssued(): array
    {
        $stmt = $this->db->query("SELECT * FROM invoices WHERE status = 'issued' ORDER BY due_date ASC");

        return array_map([Invoice::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Месечен приход (нето, во MKD) за издадени/платени фактури од $since наваму —
     * за трендот на контролната табла.
     *
     * @return array<int, array{ym: string, total: string}>
     */
    public function monthlyRevenueTotals(string $since): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(invoice_date, '%Y-%m') AS ym, SUM(total_net * exchange_rate) AS total
             FROM invoices
             WHERE status IN ('issued', 'paid') AND invoice_date >= ?
             GROUP BY ym
             ORDER BY ym ASC"
        );
        $stmt->execute([$since]);

        return $stmt->fetchAll();
    }

    public function nextNumber(): string
    {
        $year = date('Y');

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM invoices WHERE number LIKE ?"
        );
        $stmt->execute(["FAK-$year-%"]);
        $count = (int) $stmt->fetchColumn();

        return sprintf('FAK-%s-%04d', $year, $count + 1);
    }

    public function create(Invoice $invoice): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO invoices (partner_id, currency_id, exchange_rate, number, invoice_date, due_date, status, total_net, total_vat, total_gross)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $invoice->partnerId,
            $invoice->currencyId,
            $invoice->exchangeRate,
            $invoice->number,
            $invoice->date,
            $invoice->dueDate,
            $invoice->status,
            $invoice->totalNet,
            $invoice->totalVat,
            $invoice->totalGross,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertLine(InvoiceLine $line): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO invoice_lines (invoice_id, product_id, service_id, description, quantity, unit_price, account_id, vat_rate_id, vat_rate, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->invoiceId,
            $line->productId,
            $line->serviceId,
            $line->description,
            $line->quantity,
            $line->unitPrice,
            $line->accountId,
            $line->vatRateId,
            $line->vatRate,
            $line->lineTotal,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE invoices SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function markIssued(int $id, int $journalEntryId): void
    {
        $stmt = $this->db->prepare('UPDATE invoices SET status = ?, journal_entry_id = ? WHERE id = ?');
        $stmt->execute(['issued', $journalEntryId, $id]);
    }

    public function recordEinvoiceSent(int $id, string $euid): void
    {
        $stmt = $this->db->prepare(
            "UPDATE invoices SET einvoice_status = 'sent', einvoice_euid = ?, einvoice_error = NULL, einvoice_sent_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$euid, $id]);
    }

    public function recordEinvoiceError(int $id, string $message): void
    {
        $stmt = $this->db->prepare("UPDATE invoices SET einvoice_status = 'error', einvoice_error = ? WHERE id = ?");
        $stmt->execute([$message, $id]);
    }
}
