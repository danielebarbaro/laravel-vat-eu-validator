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
            'base_url' => env('VIES_API_BASE_URL', ViesRestClient::BASE_URL),
            'api_key_id' => env('VIES_API_KEY_ID'),
            'api_key' => env('VIES_API_KEY'),
        ],
    ],
];
