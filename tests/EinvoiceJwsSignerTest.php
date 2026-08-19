<?php

declare(strict_types=1);

namespace Tests;

use App\Service\Einvoice\JwsSigner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EinvoiceJwsSignerTest extends TestCase
{
    public function test_it_produces_a_valid_compact_jws_signed_with_the_matching_public_key(): void
    {
        [$privateKeyPem, $certificateDer, $publicKeyPem] = $this->generateSelfSignedKeyPair();

        $signer = JwsSigner::fromKeyMaterial($privateKeyPem, $certificateDer);
        $jws = $signer->sign(['requestTimestamp' => '2026-08-19T10:00:00', 'euid' => 'test-euid']);

        $parts = explode('.', $jws);
        $this->assertCount(3, $parts, 'JWS мора да има точно 3 дела (header.payload.signature).');

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        $this->assertSame('RS256', $header['alg']);

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        $this->assertSame('test-euid', $payload['euid']);

        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $signature = self::base64UrlDecode($encodedSignature);

        $verified = openssl_verify($signingInput, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
        $this->assertSame(1, $verified, 'Потписот мора да се верификува со јавниот клуч од истиот сертификат.');
    }

    public function test_it_refuses_to_sign_when_no_certificate_is_configured(): void
    {
        $config = new \App\Service\Einvoice\EinvoiceConfig([
            'api_base_url' => 'https://efakturatest.ujp.gov.mk/einvoice_api',
            'send_url' => 'https://efakturatest.ujp.gov.mk/JSONReceiver/api/v1/sales-invoices/send',
            'eujp_id' => 'some-id',
            'edb' => '4030995135699',
            'cert_path' => null,
            'cert_password' => null,
            'cert_serial' => null,
            'seller' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/сертификатот не е конфигуриран/i');

        JwsSigner::fromConfig($config);
    }

    /** @return array{0: string, 1: string, 2: string} [privateKeyPem, certificateDer, publicKeyPem] */
    private function generateSelfSignedKeyPair(): array
    {
        $opensslConfig = $this->findOpensslConfig();
        $keyOptions = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];

        if ($opensslConfig !== null) {
            $keyOptions['config'] = $opensslConfig;
        }

        $privateKey = openssl_pkey_new($keyOptions);

        if ($privateKey === false) {
            self::markTestSkipped('OpenSSL не може да генерира клуч на овој систем (нема openssl.cnf) — не е поврзано со кодот, туку со локалната PHP инсталација.');
        }

        $csr = openssl_csr_new(['CN' => 'Test E-invoice Signer'], $privateKey, $opensslConfig !== null ? ['config' => $opensslConfig] : []);
        $x509 = openssl_csr_sign($csr, null, $privateKey, 365, $opensslConfig !== null ? ['config' => $opensslConfig] : []);

        openssl_pkey_export($privateKey, $privateKeyPem, null, $opensslConfig !== null ? ['config' => $opensslConfig] : []);
        openssl_x509_export($x509, $certificatePem);

        $publicKeyDetails = openssl_pkey_get_details($privateKey);

        $der = base64_decode(trim(str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
            '',
            $certificatePem
        )));

        return [$privateKeyPem, $der, $publicKeyDetails['key']];
    }

    /** Некои Windows PHP инсталации немаат OPENSSL_CONF поставено — бараме openssl.cnf на познати места пред да откажеме тестот. */
    private function findOpensslConfig(): ?string
    {
        $candidates = array_filter([
            getenv('OPENSSL_CONF') ?: null,
            dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/etc/pki/tls/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
