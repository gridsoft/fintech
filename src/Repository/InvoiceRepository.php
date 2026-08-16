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
            'INSERT INTO invoices (partner_id, nalog_id, number, invoice_date, due_date, status, total_net, total_vat, total_gross)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $invoice->partnerId,
            $invoice->nalogId,
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
            'INSERT INTO invoice_lines (invoice_id, description, quantity, unit_price, vat_rate, line_total)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->invoiceId,
            $line->description,
            $line->quantity,
            $line->unitPrice,
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

    public function markIssued(int $id, int $terkId, int $journalEntryId): void
    {
        $stmt = $this->db->prepare('UPDATE invoices SET status = ?, terk_id = ?, journal_entry_id = ? WHERE id = ?');
        $stmt->execute(['issued', $terkId, $journalEntryId, $id]);
    }
}
