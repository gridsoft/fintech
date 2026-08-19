<?php

declare(strict_types=1);

namespace App\Service\Einvoice;

use RuntimeException;

/**
 * Потпишува payload-и за УЈП е-фактура API-то како compact JWS (RS256),
 * користејќи вграденото OpenSSL на PHP — нема нов Composer package за ова,
 * форматот (base64url(header).base64url(payload).base64url(signature)) е
 * едноставен и стабилен (RFC 7515).
 *
 * Претпоставува дека сертификатот е достапен како .p12/.pfx фајл читлив од
 * PHP процесот. АКО клиентскиот квалификуван сертификат излезе да е хардверски
 * токен (се потпишува преку Windows CryptoAPI, не преку читлив приватен клуч),
 * оваа класа не е доволна — ќе треба сервис за потпишување на друга машина.
 * Не е потврдено сè уште кој случај важи (нема клиент), видени DECISIONS.md.
 */
class JwsSigner
{
    private string $privateKeyPem;
    private string $certificateDer;

    private function __construct(string $privateKeyPem, string $certificateDer)
    {
        $this->privateKeyPem = $privateKeyPem;
        $this->certificateDer = $certificateDer;
    }

    public static function fromConfig(EinvoiceConfig $config): self
    {
        if (!$config->isConfiguredForSigning()) {
            throw new RuntimeException(
                'УЈП сертификатот не е конфигуриран (UJP_EINVOICE_CERT_PATH). ' .
                'Потпишувањето на е-фактура барања не е можно додека клиентот не достави сертификат.'
            );
        }

        if (!is_file($config->certPath)) {
            throw new RuntimeException("Сертификатскиот фајл не постои: {$config->certPath}");
        }

        $pkcs12 = file_get_contents($config->certPath);

        if ($pkcs12 === false) {
            throw new RuntimeException("Не можам да го прочитам сертификатскиот фајл: {$config->certPath}");
        }

        $certs = [];
        $password = $config->certPassword ?? '';

        if (!openssl_pkcs12_read($pkcs12, $certs, $password)) {
            throw new RuntimeException(
                'Неуспешно читање на .p12/.pfx сертификатот (погрешна лозинка или неподдржан формат). ' .
                'Ако сертификатот е хардверски токен (не .p12 фајл), потребен е друг механизам за потпишување.'
            );
        }

        $certDer = self::pemCertToDer($certs['cert']);

        return new self($certs['pkey'], $certDer);
    }

    /** Само за тестови — конструира signer директно од PEM клуч + DER сертификат. */
    public static function fromKeyMaterial(string $privateKeyPem, string $certificateDer): self
    {
        return new self($privateKeyPem, $certificateDer);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * НАПОМЕНА: точната содржина на JWS header-от (дали УЈП бара x5c/kid и сл.,
     * покрај "alg") не е потврдена без жив повик кон sandbox — овде се користи
     * минимален стандарден header. Ревидирај штом првиот жив тест со реален
     * сертификат покаже дали УЈП бара нешто повеќе.
     */
    public function sign(array $payload): string
    {
        $header = [
            'alg' => 'RS256',
            'x5c' => [base64_encode($this->certificateDer)],
        ];

        $encodedHeader = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $signingInput = $encodedHeader . '.' . $encodedPayload;

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $this->privateKeyPem, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new RuntimeException('Потпишувањето на JWS не успеа: ' . (openssl_error_string() ?: 'непозната грешка'));
        }

        return $signingInput . '.' . self::base64UrlEncode($signature);
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function pemCertToDer(string $pem): string
    {
        $pem = preg_replace('/-----(BEGIN|END) CERTIFICATE-----/', '', $pem);
        $pem = trim(str_replace(["\r", "\n"], '', $pem));

        return base64_decode($pem);
    }
}
