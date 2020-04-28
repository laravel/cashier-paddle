<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paddle Keys
    |--------------------------------------------------------------------------
    |
    | The Paddle vendor id and auth code give you access to Paddle's
    | API. The "public" key is typically used when interacting with
    | Paddle.js while the "secret" key accesses private API endpoints.
    |
    */

    'vendor_id' => env('PADDLE_VENDOR_ID'),

    'vendor_auth_code' => env('PADDLE_VENDOR_AUTH_CODE'),

    'public_key' => env('PADDLE_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where Cashier's views, such as the
    | webhook route, will be available from. You're free to tweak
    | this path according to your preferences and application design.
    |
    */

    'path' => env('CASHIER_PATH', 'paddle'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Webhook
    |--------------------------------------------------------------------------
    |
    | This is the base URI where webhooks from Paddle will be sent to.
    | By default the built in url will be used but you can optionally
    | set a url which could be useful for testing purposes.
    |
    */

    'webhook' => env('CASHIER_WEBHOOK'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Model
    |--------------------------------------------------------------------------
    |
    | This is the model in your application that implements the Billable trait
    | provided by Cashier. It will serve as the primary model you use while
    | interacting with Cashier related methods, subscriptions, and so on.
    |
    */

    'model' => env('CASHIER_MODEL', App\User::class),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various world currencies that are currently supported via Paddle.
    |
    */

    'currency' => env('CASHIER_CURRENCY', 'usd'),

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
    | Invoice Paper Size
    |--------------------------------------------------------------------------
    |
    | This option is the default paper size for all invoices generated using
    | Cashier. You are free to customize this settings based on the usual
    | paper size used by the customers using your Laravel applications.
    |
    | Supported sizes: 'letter', 'legal', 'A4'
    |
    */

    'paper' => env('CASHIER_PAPER', 'letter'),

];
