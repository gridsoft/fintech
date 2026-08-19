<?php

declare(strict_types=1);

namespace App\Service\Einvoice;

use RuntimeException;

/**
 * Тенок клиент за УЈП е-фактура API-то (efakturatest.ujp.gov.mk).
 * Нема нов Composer package — cURL + вградениот json_encode/openssl се
 * доволни за ова. Секој јавен метод експлицитно паѓа со јасна порака ако
 * eujp_id/edb (или, за потпишани повици, сертификатот) не се конфигурирани —
 * нема тивко mock-ување, бидејќи сè уште нема реален клиент/тест профил.
 *
 * Имплементирани се сите нештитени reference-data сервиси + еден
 * репрезентативен потпишан тек (sendSalesInvoice/currentStatus), за да го
 * докаже целиот механизам (config → JWS потпис → HTTP). Останатите документ-
 * операции (accept/reject, storno, PDF, payload...) следат истиот образец
 * преку signedPost() и се додаваат кога реално затребаат — нема причина да
 * се пишуваат сите 20-тина сега без начин да се тестираат.
 */
class UjpEinvoiceClient
{
    private EinvoiceConfig $config;
    private ?JwsSigner $signer;

    public function __construct(EinvoiceConfig $config, ?JwsSigner $signer = null)
    {
        $this->config = $config;
        $this->signer = $signer;
    }

    public function serverTime(): array
    {
        return $this->get('/api/v1/server-time');
    }

    public function currencies(): array
    {
        return $this->get('/api/v1/currency');
    }

    public function currencyExchangeRate(string $currencyName, string $date): array
    {
        return $this->post('/api/v1/currency-exchange/rate', [
            'requestTimestamp' => $this->requestTimestamp(),
            'currencyName' => $currencyName,
            'date' => $date,
        ]);
    }

    public function countries(): array
    {
        return $this->get('/api/v1/countries');
    }

    public function voidReasons(): array
    {
        return $this->get('/api/v1/void-reasons');
    }

    public function rejectReasons(): array
    {
        return $this->get('/api/v1/reject-reasons');
    }

    public function paymentTypes(): array
    {
        return $this->get('/api/v1/payment-types');
    }

    public function documentStatuses(): array
    {
        return $this->get('/api/v1/document-statuses');
    }

    public function documentTypes(): array
    {
        return $this->get('/api/v1/document-types');
    }

    public function taxGroups(): array
    {
        return $this->get('/api/v1/tax-groups');
    }

    public function taxIndicators(): array
    {
        return $this->get('/api/v1/tax-indicators');
    }

    public function company(string $taxNumber): array
    {
        return $this->get('/api/v1/companies/' . rawurlencode($taxNumber));
    }

    public function subsidiary(string $taxNumber): array
    {
        return $this->get('/api/v1/subsidiary/' . rawurlencode($taxNumber));
    }

    /**
     * Праќа нова излезна е-фактура. $document е "document" делот од payload-от
     * (види SalesInvoicePayloadBuilder) — requestTimestamp се додава тука.
     *
     * НЕПОТВРДЕНО без жив тест: дали телото на потпишан повик е самиот
     * compact JWS string (претпоставка овде) или JSON обвивка околу него.
     * Провери со првиот жив повик кон sandbox и коригирај ако е потребно.
     */
    public function sendSalesInvoice(array $document): array
    {
        $payload = [
            'requestTimestamp' => $this->requestTimestamp(),
            'document' => $document,
        ];

        return $this->signedPost($this->config->sendUrl, $payload);
    }

    public function currentSalesInvoiceStatus(string $euid): array
    {
        return $this->signedPost($this->config->apiBaseUrl . '/api/v1/documents/sales-invoice/current-status', [
            'requestTimestamp' => $this->requestTimestamp(),
            'euid' => $euid,
        ]);
    }

    private function get(string $path): array
    {
        return $this->request('GET', $this->config->apiBaseUrl . $path, null, false);
    }

    private function post(string $path, array $body): array
    {
        return $this->request('POST', $this->config->apiBaseUrl . $path, $body, false);
    }

    private function signedPost(string $url, array $body): array
    {
        return $this->request('POST', $url, $body, true);
    }

    private function request(string $method, string $url, ?array $body, bool $signed): array
    {
        if (!$this->config->isConfiguredForApi()) {
            throw new RuntimeException(
                'УЈП е-фактура не е конфигурирана (UJP_EINVOICE_EUJP_ID / UJP_EINVOICE_EDB се празни). ' .
                'Нема клиент со тест-пристап сè уште — ова е очекувано додека не се внесат вистински вредности.'
            );
        }

        $headers = [
            'X-EUJP-ID: ' . $this->config->eujpId,
            'X-EDB: ' . $this->config->edb,
        ];

        $requestBody = null;

        if ($signed) {
            $signer = $this->signer ?? JwsSigner::fromConfig($this->config);
            $requestBody = $signer->sign($body);
            $headers[] = 'Content-Type: application/jose';
            $headers[] = 'X-SERIAL-NUMBER: ' . ($this->config->certSerial ?? '');
        } elseif ($body !== null) {
            $requestBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($requestBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Повикот кон УЈП е-фактура не успеа: $error");
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($statusCode >= 400) {
            throw new RuntimeException("УЈП е-фактура врати грешка ($statusCode): $response");
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('УЈП е-фактура врати одговор кој не е валиден JSON: ' . substr($response, 0, 500));
        }

        return $decoded;
    }

    private function requestTimestamp(): string
    {
        return date('Y-m-d\TH:i:s');
    }
}
