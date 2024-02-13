<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paddle Keys
    |--------------------------------------------------------------------------
    |
    | The Paddle seller ID and API key will allow your application to call
    | the Paddle API. The seller key is typically used when interacting
    | with Paddle.js, while the "API" key accesses private endpoints.
    |
    */

    'seller_id' => env('PADDLE_SELLER_ID'),

    'client_side_token' => env('PADDLE_CLIENT_SIDE_TOKEN'),

    'api_key' => env('PADDLE_AUTH_CODE') ?? env('PADDLE_API_KEY'),

    'retain_key' => env('PADDLE_RETAIN_KEY'),

    'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where Cashier's views, such as the webhook
    | route, will be available. You're free to tweak this path based on
    | the needs of your particular application or design preferences.
    |
    */

    'path' => env('CASHIER_PATH', 'paddle'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Webhook
    |--------------------------------------------------------------------------
    |
    | This is the base URI where webhooks from Paddle will be sent. The URL
    | built into Cashier Paddle is used by default; however, you can add
    | a custom URL when required for any application testing purposes.
    |
    */

    'webhook' => env('CASHIER_WEBHOOK'),

    /*
    |--------------------------------------------------------------------------
    | Currency Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale in which your money values are formatted in
    | for display. To utilize other locales besides the default en locale
    | verify you have the "intl" PHP extension installed on the system.
    |
    */

    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Paddle Sandbox
    |--------------------------------------------------------------------------
    |
    | This option allows you to toggle between the Paddle live environment
    | and its sandboxed environment.
    |
    */

    'sandbox' => env('PADDLE_SANDBOX', false),

];
