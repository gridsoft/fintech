<?php

declare(strict_types=1);

namespace App\Service\Einvoice;

use App\Repository\CurrencyRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Ја оркестрира рачната акција „Прати како е-фактура“ (копче на веќе
 * издадена фактура — намерно нема автоматско праќање, види DECISIONS.md).
 * Секој неуспех (не е конфигурирано, ДДВ стапка без мапиран код, УЈП врати
 * грешка...) се запишува на фактурата (einvoice_status='error') и се
 * проследува понатаму — контролерот го прикажува на корисникот.
 */
class SalesInvoiceEinvoiceService
{
    private const RESENDABLE_STATUSES = ['not_sent', 'error', 'rejected'];

    private InvoiceRepository $invoices;
    private PartnerRepository $partners;
    private CurrencyRepository $currencies;
    private SalesInvoicePayloadBuilder $payloadBuilder;
    private ?UjpEinvoiceClient $client;

    public function __construct(
        ?InvoiceRepository $invoices = null,
        ?PartnerRepository $partners = null,
        ?CurrencyRepository $currencies = null,
        ?SalesInvoicePayloadBuilder $payloadBuilder = null,
        ?UjpEinvoiceClient $client = null
    ) {
        $this->invoices = $invoices ?? new InvoiceRepository();
        $this->partners = $partners ?? new PartnerRepository();
        $this->currencies = $currencies ?? new CurrencyRepository();
        $this->payloadBuilder = $payloadBuilder ?? new SalesInvoicePayloadBuilder();
        $this->client = $client;
    }

    public function send(int $invoiceId): void
    {
        $invoice = $this->invoices->find($invoiceId);

        if (!$invoice) {
            throw new InvalidArgumentException('Фактурата не е пронајдена.');
        }

        if ($invoice->status !== 'issued' && $invoice->status !== 'paid') {
            throw new RuntimeException('Само издадена фактура може да се прати како е-фактура.');
        }

        if (!in_array($invoice->einvoiceStatus, self::RESENDABLE_STATUSES, true)) {
            throw new RuntimeException('Фактурата веќе е пратена/прифатена како е-фактура.');
        }

        $buyer = $this->partners->find($invoice->partnerId);

        if (!$buyer) {
            throw new RuntimeException('Партнерот на фактурата не постои.');
        }

        $currency = $this->currencies->find($invoice->currencyId);

        if (!$currency) {
            throw new RuntimeException('Валутата на фактурата не постои.');
        }

        $config = EinvoiceConfig::fromAppConfig();
        $client = $this->client ?? new UjpEinvoiceClient($config);

        try {
            $document = $this->payloadBuilder->build($invoice, $buyer, $currency, $config);
            $response = $client->sendSalesInvoice($document);

            $euid = $response['euid'] ?? null;

            if (!$euid) {
                throw new RuntimeException('УЈП не врати euid во одговорот: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
            }

            $this->invoices->recordEinvoiceSent($invoiceId, (string) $euid);
        } catch (Throwable $e) {
            $this->invoices->recordEinvoiceError($invoiceId, $e->getMessage());
            throw $e;
        }
    }
}
