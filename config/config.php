<?php

$envPath = __DIR__ . '/../.env';

if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

$env = static function (string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'app' => [
        'env' => $env('APP_ENV', 'local'),
        'debug' => filter_var($env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    ],
    'db' => [
        'driver' => $env('DB_DRIVER', 'mysql'),
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '3306'),
        'database' => $env('DB_DATABASE', 'fintech'),
        'username' => $env('DB_USERNAME', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    // УЈП е-фактура. api_base_url/send_url се однапред пополнети со официјалната
    // тест (sandbox) околина — безопасно, јавно документирани URL-и. Сè
    // клиент-специфично (eujp_id/edb/сертификат/седиште) останува null додека
    // не се внесат вистински вредности — клиентот сè уште не постои.
    'einvoice' => [
        'api_base_url' => $env('UJP_EINVOICE_API_BASE_URL', 'https://efakturatest.ujp.gov.mk/einvoice_api'),
        'send_url' => $env('UJP_EINVOICE_SEND_URL', 'https://efakturatest.ujp.gov.mk/JSONReceiver/api/v1/sales-invoices/send'),
        'eujp_id' => $env('UJP_EINVOICE_EUJP_ID'),
        'edb' => $env('UJP_EINVOICE_EDB'),
        'cert_path' => $env('UJP_EINVOICE_CERT_PATH'),
        'cert_password' => $env('UJP_EINVOICE_CERT_PASSWORD'),
        'cert_serial' => $env('UJP_EINVOICE_CERT_SERIAL'),
        'seller' => [
            'tin' => $env('UJP_EINVOICE_SELLER_TIN'),
            'vat_number' => $env('UJP_EINVOICE_SELLER_VAT_NUMBER'),
            'name' => $env('UJP_EINVOICE_SELLER_NAME'),
            'country_code' => $env('UJP_EINVOICE_SELLER_COUNTRY_CODE', 'MK'),
            'street' => $env('UJP_EINVOICE_SELLER_STREET'),
            'number' => $env('UJP_EINVOICE_SELLER_STREET_NUMBER'),
            'postal_code' => $env('UJP_EINVOICE_SELLER_POSTAL_CODE'),
            'city' => $env('UJP_EINVOICE_SELLER_CITY'),
        ],
    ],
];
