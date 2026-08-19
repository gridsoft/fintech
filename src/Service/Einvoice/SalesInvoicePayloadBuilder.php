<?php

declare(strict_types=1);

namespace App\Service\Einvoice;

use App\Domain\Accounting\Currency;
use App\Domain\Accounting\VatRate;
use App\Domain\Invoicing\Invoice;
use App\Domain\Invoicing\InvoiceLine;
use App\Domain\Partners\Partner;
use App\Repository\VatRateRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Гради го "document" делот на JSON payload-от за излезна е-фактура (docType
 * 100), точно според official api-documentation-public7.pdf /
 * json_primeri_13.8.2026.pdf. Не прави HTTP повици — чист трансформатор,
 * тестлив без жива УЈП врска (види EinvoiceSalesInvoicePayloadBuilderTest).
 *
 * Намерно опфаќа само обична продажна фактура (docType 100). Сторно/
 * корекција/книжни известија/аванси имаат сопствена структура во доцот и се
 * доградуваат кога реално затребаат.
 */
class SalesInvoicePayloadBuilder
{
    private const DOC_TYPE = '100';
    private const DOC_TYPE_NAME = 'Фактура';

    /** Немаме поле за единица мерка на линија — плацехолдер додека не се доразработи. */
    private const DEFAULT_UNIT = 'ком.';

    private VatRateRepository $vatRates;

    public function __construct(?VatRateRepository $vatRates = null)
    {
        $this->vatRates = $vatRates ?? new VatRateRepository();
    }

    public function build(Invoice $invoice, Partner $buyer, Currency $currency, EinvoiceConfig $seller): array
    {
        if (!$seller->hasSellerProfile()) {
            throw new RuntimeException(
                'Седиштето на компанијата за е-фактура не е конфигурирано (UJP_EINVOICE_SELLER_TIN/NAME).'
            );
        }

        if (count($invoice->lines) < 1) {
            throw new InvalidArgumentException('Фактурата нема ставки.');
        }

        $items = [];
        $vatGroups = [];

        foreach ($invoice->lines as $index => $line) {
            $vatRate = $this->vatRates->find($line->vatRateId);

            if (!$vatRate) {
                throw new RuntimeException("ДДВ стапката на линија #{$line->id} повеќе не постои.");
            }

            if (!$vatRate->ujpTaxIndicatorCode) {
                throw new RuntimeException(
                    "ДДВ стапката „{$vatRate->name}“ нема мапиран УЈП даночен индикатор — постави го во /vat-rates пред испраќање."
                );
            }

            $items[] = $this->buildItem($index + 1, $line, $vatRate);

            $key = $vatRate->ujpTaxIndicatorCode;

            if (!isset($vatGroups[$key])) {
                $vatGroups[$key] = [
                    'vatRate' => $vatRate,
                    'taxableAmount' => 0.0,
                    'vatAmount' => 0.0,
                ];
            }

            $vatGroups[$key]['taxableAmount'] += (float) $line->lineTotal;
            $vatGroups[$key]['vatAmount'] += $line->vatAmount();
        }

        return [
            'header' => [
                'docStorno' => 0,
                'docType' => self::DOC_TYPE,
                'docTypeName' => self::DOC_TYPE_NAME,
                'docDate' => $invoice->date,
                'docTurnoverDate' => $invoice->date,
                'docNumber' => $invoice->number,
                'docId' => (string) $invoice->id,
                'docNotes' => null,
                'docHeader' => null,
                'docFooter' => null,
            ],
            'seller' => $this->buildSeller($seller),
            'buyer' => $this->buildBuyer($buyer),
            'docPayment' => [
                'docPaymentTypeCode' => null,
                'docPaymentTypeDesc' => null,
                'docPaymentTypeDueDays' => null,
                'docPaymentTypeDueDate' => $invoice->dueDate,
                'docPaymentTerms' => null,
                'docPaymentNote' => null,
                'docPaymentInterest' => null,
                'docPaymentDiscount' => null,
                'docCurrency' => $currency->code,
                'docCurrencyCode' => $currency->code,
                'docCurrencyDate' => $invoice->date,
                'docCurrencyExchRate' => (float) $invoice->exchangeRate,
            ],
            'docItems' => $items,
            'docTotals' => [
                'docNetAmount' => (float) $invoice->totalNet,
                'docDiscountAmount' => 0,
                'docNetAmountDisc' => (float) $invoice->totalNet,
                'docVatAmount' => (float) $invoice->totalVat,
                'docGrossAmount' => (float) $invoice->totalGross,
                'docGrossAmountR' => round((float) $invoice->totalGross),
                'docAvansDate' => null,
                'docAvansDesc' => null,
                'docAvansAmount' => 0,
                'docFinalAmount' => (float) $invoice->totalGross,
            ],
            'vatTotals' => array_values(array_map(function (array $group): array {
                /** @var VatRate $vatRate */
                $vatRate = $group['vatRate'];
                $taxable = round($group['taxableAmount'], 2);
                $vat = round($group['vatAmount'], 2);

                return [
                    'vatTaxIndicator' => $vatRate->ujpTaxIndicatorCode,
                    'vatTaxIndicatorNote' => '',
                    'vatCode' => $vatRate->ujpTaxIndicatorCode,
                    'vatPercent' => (float) $vatRate->rate,
                    'vatTaxableAmount' => $taxable,
                    'vatAmount' => $vat,
                    'vatTotalAmount' => round($taxable + $vat, 2),
                ];
            }, $vatGroups)),
        ];
    }

    private function buildItem(int $lineNo, InvoiceLine $line, VatRate $vatRate): array
    {
        $qty = (float) $line->quantity;
        $lineTotal = (float) $line->lineTotal;
        $vatAmount = $line->vatAmount();

        return [
            'docItemLineNo' => $lineNo,
            'docItemSku' => null,
            'docItemSenderCode' => $line->productId !== null
                ? (string) $line->productId
                : ($line->serviceId !== null ? (string) $line->serviceId : null),
            'docItemReceiverCode' => null,
            'docItemDesc' => $line->description ?? '',
            'docItemMUnit' => self::DEFAULT_UNIT,
            'docItemQty' => $qty,
            'docItemUnitOriginalPriceWoVat' => (float) $line->unitPrice,
            'docItemUnitDiscountAmount' => 0,
            'docItemUnitPriceWoVat' => (float) $line->unitPrice,
            'docItemUnitVat' => $qty > 0 ? round($vatAmount / $qty, 4) : 0,
            'docItemVat' => (float) $vatRate->rate,
            'docItemVatGroup' => $vatRate->ujpTaxIndicatorCode,
            'docItemTotalOriginalPriceWoVat' => $lineTotal,
            'docItemTotalPriceWoVat' => $lineTotal,
            'docItemTotalVat' => $vatAmount,
            'docItemTotalPriceWVat' => round($lineTotal + $vatAmount, 2),
            'docItemTaxIndicator' => $vatRate->ujpTaxIndicatorCode,
            'docItemDomesticProduct' => null,
        ];
    }

    private function buildSeller(EinvoiceConfig $seller): array
    {
        return [
            'sellerCCode' => $seller->sellerCountryCode,
            'sellerCName' => $this->countryName($seller->sellerCountryCode),
            'sellerTin' => $seller->sellerTin,
            'sellerForeignTin' => null,
            'sellerVatNumber' => $seller->sellerVatNumber,
            'sellerName' => $seller->sellerName,
            'sellerAddress' => [
                'streetAddress' => $seller->sellerStreet,
                'streetNumber' => $seller->sellerStreetNumber,
                'postalCode' => $seller->sellerPostalCode,
                'city' => $seller->sellerCity,
            ],
            'sellerContact' => null,
            'sellerEmail' => null,
        ];
    }

    private function buildBuyer(Partner $buyer): array
    {
        $isForeign = $buyer->isForeign();
        $streetAddress = trim(($buyer->addressLine1 ?? '') . ' ' . ($buyer->addressLine2 ?? ''));

        return [
            'buyerCCode' => $buyer->country,
            'buyerCName' => $this->countryName($buyer->country),
            'buyerTin' => $isForeign ? null : $buyer->taxNumber,
            'buyerForeignTin' => $isForeign ? $buyer->taxNumber : null,
            'buyerVatNumber' => $buyer->vatNumber,
            'buyerName' => $buyer->name,
            'buyerAddress' => [
                'streetAddress' => $streetAddress !== '' ? $streetAddress : null,
                'streetNumber' => null,
                'postalCode' => $buyer->postalCode,
                'city' => $buyer->city,
            ],
            'buyerContact' => $buyer->phone ?? $buyer->mobile,
            'buyerEmail' => $buyer->email,
        ];
    }

    /** Немаме табела држави (нема потреба досега) — само MK е познато локално. */
    private function countryName(string $countryCode): ?string
    {
        return $countryCode === 'MK' ? 'Северна Македонија' : null;
    }
}
