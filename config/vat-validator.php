<?php

use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;

return [
    /*
    |--------------------------------------------------------------------------
    | VIES Client Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls which VIES client is used to validate VAT numbers.
    |
    | Available clients: ViesSoapClient::CLIENT_NAME, ViesRestClient::CLIENT_NAME
    |
    */

    'client' => ViesSoapClient::CLIENT_NAME,

    'clients' => [
        ViesSoapClient::CLIENT_NAME => [
            'timeout' => 10,
        ],

        ViesRestClient::CLIENT_NAME => [
            'timeout' => 10,
            'base_url' => env('VIES_REST_BASE_URL', ViesRestClient::BASE_URL),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VIES Failure Handling
    |--------------------------------------------------------------------------
    |
    | What the vat_number and vat_number_exist rules do when VIES cannot
    | answer (unreachable, timeout, member state unavailable, too many
    | requests). The facade and the service always throw a ViesException.
    |
    | "throw": the ViesException escapes the validator (default).
    | "fail":  the rule fails with the vies_unavailable message.
    | "pass":  the rule passes; the format check still applies.
    |
    */

    'on_vies_failure' => env('VIES_ON_FAILURE', 'throw'),
];
