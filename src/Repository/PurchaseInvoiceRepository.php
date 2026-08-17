<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Invoicing\PurchaseInvoice;
use App\Domain\Invoicing\PurchaseInvoiceLine;
use PDO;

class PurchaseInvoiceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return PurchaseInvoice[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM purchase_invoices ORDER BY invoice_date DESC, id DESC');

        return array_map([PurchaseInvoice::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Заведени влезни фактури на еден партнер, сè уште не целосно платени — единствените што можат да се поврзат со исплата. */
    public function openForMatchingByPartner(int $partnerId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM purchase_invoices WHERE status = 'posted' AND partner_id = ? ORDER BY invoice_date ASC, id ASC");
        $stmt->execute([$partnerId]);

        return array_map([PurchaseInvoice::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?PurchaseInvoice
    {
        $stmt = $this->db->prepare('SELECT * FROM purchase_invoices WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $invoice = PurchaseInvoice::fromRow($row);
        $invoice->lines = $this->linesForInvoice($id);

        return $invoice;
    }

    public function existsForPartnerAndNumber(int $partnerId, string $supplierNumber): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM purchase_invoices WHERE partner_id = ? AND supplier_number = ?');
        $stmt->execute([$partnerId, $supplierNumber]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return PurchaseInvoiceLine[] */
    public function linesForInvoice(int $purchaseInvoiceId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM purchase_invoice_lines WHERE purchase_invoice_id = ? ORDER BY id ASC');
        $stmt->execute([$purchaseInvoiceId]);

        return array_map([PurchaseInvoiceLine::class, 'fromRow'], $stmt->fetchAll());
    }

    public function create(PurchaseInvoice $invoice): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO purchase_invoices (partner_id, supplier_number, invoice_date, due_date, status, total_net, total_vat, total_gross)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $invoice->partnerId,
            $invoice->supplierNumber,
            $invoice->date,
            $invoice->dueDate,
            $invoice->status,
            $invoice->totalNet,
            $invoice->totalVat,
            $invoice->totalGross,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertLine(PurchaseInvoiceLine $line): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO purchase_invoice_lines (purchase_invoice_id, expense_category_id, description, quantity, unit_price, account_id, vat_rate_id, vat_rate, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $line->purchaseInvoiceId,
            $line->expenseCategoryId,
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
        $stmt = $this->db->prepare('UPDATE purchase_invoices SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function markPosted(int $id, int $journalEntryId): void
    {
        $stmt = $this->db->prepare('UPDATE purchase_invoices SET status = ?, journal_entry_id = ? WHERE id = ?');
        $stmt->execute(['posted', $journalEntryId, $id]);
    }
}
