<?php

return [
    'default_environment' => env('SUNAT_ENVIRONMENT', 'beta'),
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
