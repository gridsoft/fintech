<?php

declare(strict_types=1);

namespace App\Service\Einvoice;

/**
 * Обвивка околу config/config.php['einvoice']. Останува "неконфигурирана"
 * (isConfiguredForApi/isConfiguredForSigning враќаат false) додека
 * eujp_id/edb/сертификат не се пополнат во .env — нема mock/лажни вредности,
 * повикувачите мора експлицитно да проверат пред да се обидат да контактираат УЈП.
 */
class EinvoiceConfig
{
    public string $apiBaseUrl;
    public string $sendUrl;
    public ?string $eujpId;
    public ?string $edb;
    public ?string $certPath;
    public ?string $certPassword;
    public ?string $certSerial;

    public ?string $sellerTin;
    public ?string $sellerVatNumber;
    public ?string $sellerName;
    public string $sellerCountryCode;
    public ?string $sellerStreet;
    public ?string $sellerStreetNumber;
    public ?string $sellerPostalCode;
    public ?string $sellerCity;

    public function __construct(array $config)
    {
        $this->apiBaseUrl = rtrim($config['api_base_url'], '/');
        $this->sendUrl = $config['send_url'];
        $this->eujpId = $this->nullIfBlank($config['eujp_id'] ?? null);
        $this->edb = $this->nullIfBlank($config['edb'] ?? null);
        $this->certPath = $this->nullIfBlank($config['cert_path'] ?? null);
        $this->certPassword = $this->nullIfBlank($config['cert_password'] ?? null);
        $this->certSerial = $this->nullIfBlank($config['cert_serial'] ?? null);

        $seller = $config['seller'] ?? [];
        $this->sellerTin = $this->nullIfBlank($seller['tin'] ?? null);
        $this->sellerVatNumber = $this->nullIfBlank($seller['vat_number'] ?? null);
        $this->sellerName = $this->nullIfBlank($seller['name'] ?? null);
        $this->sellerCountryCode = $seller['country_code'] ?? 'MK';
        $this->sellerStreet = $this->nullIfBlank($seller['street'] ?? null);
        $this->sellerStreetNumber = $this->nullIfBlank($seller['number'] ?? null);
        $this->sellerPostalCode = $this->nullIfBlank($seller['postal_code'] ?? null);
        $this->sellerCity = $this->nullIfBlank($seller['city'] ?? null);
    }

    public static function fromAppConfig(): self
    {
        $config = require __DIR__ . '/../../../config/config.php';

        return new self($config['einvoice']);
    }

    /** Дали има доволно за повици кон нештитените (reference-data) сервиси. */
    public function isConfiguredForApi(): bool
    {
        return $this->eujpId !== null && $this->edb !== null;
    }

    /** Дали има доволно за потпишани (JWS) повици — испраќање/статус/сторно документи. */
    public function isConfiguredForSigning(): bool
    {
        return $this->isConfiguredForApi() && $this->certPath !== null;
    }

    /** Дали има доволно седишни податоци за градење на sellerInfo во payload. */
    public function hasSellerProfile(): bool
    {
        return $this->sellerTin !== null && $this->sellerName !== null;
    }

    private function nullIfBlank(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
