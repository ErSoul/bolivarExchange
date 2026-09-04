<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Binance API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for accessing the Binance API.
    | You can set your API key and secret in the .env file.
    |
    */

    'api_key' => env('BINANCE_API_KEY', ''),
    'api_secret' => env('BINANCE_API_SECRET', '')
];