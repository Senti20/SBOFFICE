<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Semaphore SMS Gateway
    |--------------------------------------------------------------------------
    |
    | Configuration for the Semaphore SMS API used to send text message
    | notifications to requesters. Get your API key from https://semaphore.co
    |
    */

    'semaphore' => [
        'api_key'     => env('SEMAPHORE_API_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME', 'BontocSB'),
        'api_url'     => env('SEMAPHORE_API_URL', 'https://api.semaphore.co/api/v4/messages'),
    ],

];
