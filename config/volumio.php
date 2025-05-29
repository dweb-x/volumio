<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Volumio API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for configuring the connection to the Volumio REST API.
    | See: https://developers.volumio.com/api/rest-api
    |
    */

    // The base URL of your Volumio instance
    'base_url' => env('VOLUMIO_API_URL', 'http://localhost:3000'),

    // API request timeout in seconds
    'timeout' => env('VOLUMIO_API_TIMEOUT', 10),

    // Connection retry attempts
    'retries' => env('VOLUMIO_API_RETRIES', 3),

    // Default request options for Guzzle
    'http_options' => [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ],
];
