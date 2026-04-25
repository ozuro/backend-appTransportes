<?php

return [
    'default_environment' => env('SUNAT_ENVIRONMENT', 'beta'),
    'lucode' => [
        'service_url' => env('LUCODE_API_BASE_URL'),
        'sandbox_url' => env('LUCODE_SANDBOX_BASE_URL', 'https://sandbox.apisunat.pe'),
        'production_url' => env('LUCODE_PRODUCTION_BASE_URL', 'https://app.apisunat.pe'),
        'api_token' => env('LUCODE_API_TOKEN'),
    ],
    'sire' => [
        'security_url_template' => env(
            'SUNAT_SIRE_SECURITY_URL_TEMPLATE',
            'https://api-seguridad.sunat.gob.pe/v1/clientessol/{client_id}/oauth2/token/'
        ),
        'api_base_url' => env('SUNAT_SIRE_API_BASE_URL', 'https://api-sire.sunat.gob.pe'),
        'scope' => env('SUNAT_SIRE_SCOPE', 'https://api-sire.sunat.gob.pe'),
        'timeout' => (int) env('SUNAT_SIRE_TIMEOUT', 20),
        'client_id' => env('SUNAT_SIRE_CLIENT_ID'),
        'client_secret' => env('SUNAT_SIRE_CLIENT_SECRET'),
        'username' => env('SUNAT_SIRE_USERNAME'),
        'endpoints' => [
            'sales' => env('SUNAT_SIRE_SALES_ENDPOINT'),
            'purchases' => env('SUNAT_SIRE_PURCHASES_ENDPOINT'),
        ],
    ],
    'environments' => [
        'beta' => [
            'label' => 'SUNAT Beta',
            'soap_url' => env(
                'SUNAT_BETA_SOAP_URL',
                'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService'
            ),
        ],
        'production' => [
            'label' => 'SUNAT Produccion',
            'soap_url' => env(
                'SUNAT_PRODUCTION_SOAP_URL',
                'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService'
            ),
        ],
    ],
];
