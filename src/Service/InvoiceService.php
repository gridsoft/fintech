<?php

namespace App\Service;

use App\Core\Database;
use App\Domain\Invoicing\Invoice;
use App\Domain\Invoicing\InvoiceLine;
use App\Repository\InvoiceRepository;
use App\Repository\NalogRepository;
use App\Repository\TerkRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class InvoiceService
{
    private PDO $db;
    private InvoiceRepository $invoices;
    private NalogRepository $nalozi;
    private TerkRepository $terkovi;
    private LedgerService $ledger;

    public function __construct(
        ?InvoiceRepository $invoices = null,
        ?NalogRepository $nalozi = null,
        ?TerkRepository $terkovi = null,
        ?LedgerService $ledger = null
    ) {
        $this->db = Database::connection();
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->nalozi = $nalozi ?? new NalogRepository();
        $this->terkovi = $terkovi ?? new TerkRepository();
        $this->ledger = $ledger ?? new LedgerService();
    }

    /**
     * Создава фактура (статус draft) + линии. Вкупните износи се пресметуваат од линиите.
     *
     * @param array<int, array{description: string, quantity: string|float, unit_price: string|float, vat_rate: string|float}> $lines
     */
    public function createInvoice(int $partnerId, ?int $nalogId, string $date, string $dueDate, array $lines): int
    {
        if (count($lines) < 1) {
            throw new InvalidArgumentException('Фактурата мора да содржи барем 1 ставка.');
        }

        $normalized = [];
        $totalNet = 0.0;
        $totalVat = 0.0;

        foreach ($lines as $line) {
            $description = trim((string) ($line['description'] ?? ''));

            if ($description === '') {
                throw new InvalidArgumentException('Секоја ставка мора да има опис.');
            }

            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $vatRate = (float) ($line['vat_rate'] ?? 0);

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Количината мора да биде поголема од нула.');
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Единечната цена не може да биде негативна.');
            }

            $lineTotal = round($quantity * $unitPrice, 2);
            $lineVat = round($lineTotal * $vatRate / 100, 2);

            $totalNet += $lineTotal;
            $totalVat += $lineVat;

            $normalized[] = [
                'description' => $description,
                'quantity' => number_format($quantity, 2, '.', ''),
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'vat_rate' => number_format($vatRate, 2, '.', ''),
                'line_total' => number_format($lineTotal, 2, '.', ''),
            ];
        }

        $totalNet = round($totalNet, 2);
        $totalVat = round($totalVat, 2);
        $totalGross = round($totalNet + $totalVat, 2);

        $this->db->beginTransaction();

        try {
            $invoice = new Invoice(
                $partnerId,
                $nalogId,
                $this->invoices->nextNumber(),
                $date,
                $dueDate,
                'draft',
                number_format($totalNet, 2, '.', ''),
                number_format($totalVat, 2, '.', ''),
                number_format($totalGross, 2, '.', '')
            );

            $invoiceId = $this->invoices->create($invoice);

            foreach ($normalized as $line) {
                $this->invoices->insertLine(new InvoiceLine(
                    $line['description'],
                    $line['quantity'],
                    $line['unit_price'],
                    $line['vat_rate'],
                    $line['line_total'],
                    $invoiceId
                ));
            }

            $this->db->commit();

            return $invoiceId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ја издава фактурата: книжењето се гради динамички според теркот на нејзиниот налог
     * (секоја линија од теркот вели која сметка, дебит/кредит и од кој износ — нето/ддв/бруто).
     */
    public function issue(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Само фактура во статус „нацрт“ може да се издаде.');
        }

        if (!$invoice->nalogId) {
            throw new RuntimeException('Фактурата нема доделено налог — не може да се знае како да се книжи.');
        }

        $nalog = $this->nalozi->find($invoice->nalogId);

        if (!$nalog) {
            throw new RuntimeException('Налогот на фактурата не постои.');
        }

        $terk = $this->terkovi->find($nalog->terkId);

        if (!$terk || count($terk->lines) < 1) {
            throw new RuntimeException("Теркот „{$nalog->name}“ нема дефинирани ставки за книжење.");
        }

        $amounts = [
            'net' => $invoice->totalNet,
            'vat' => $invoice->totalVat,
            'gross' => $invoice->totalGross,
        ];

        $journalLines = [];

        foreach ($terk->lines as $terkLine) {
            $amount = $amounts[$terkLine->amountSource] ?? '0.00';

            if ((float) $amount <= 0) {
                continue;
            }

            $journalLines[] = [
                'account_id' => $terkLine->accountId,
                'partner_id' => $terkLine->tagPartner ? $invoice->partnerId : null,
                'debit' => $terkLine->side === 'debit' ? $amount : '0',
                'credit' => $terkLine->side === 'credit' ? $amount : '0',
                'description' => "Фактура {$invoice->number}",
            ];
        }

        if (count($journalLines) < 2) {
            throw new RuntimeException("Теркот „{$nalog->name}“ произведува помалку од 2 ставки за книжење — провери го теркот.");
        }

        $entryId = $this->ledger->postEntry(
            $invoice->date,
            "Фактура {$invoice->number}",
            $invoice->number,
            $journalLines
        );

        $this->invoices->updateStatus($invoiceId, 'issued', $entryId);
    }

    public function markPaid(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'issued') {
            throw new RuntimeException('Само издадена фактура може да се означи како платена.');
        }

        $this->invoices->updateStatus($invoiceId, 'paid');
    }

    public function cancel(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Само нацрт фактура може да се откаже (издадена бара сторно, идна фаза).');
        }

        $this->invoices->updateStatus($invoiceId, 'cancelled');
    }
}
