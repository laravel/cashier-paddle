<?php

namespace Laravel\Paddle;

use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Exceptions\PaddleException;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class Cashier
{
    /**
     * The Cashier Paddle library version.
     *
     * @var string
     */
    const VERSION = '0.1.0-dev';

    /**
     * The Paddle checkout URL.
     *
     * @var string
     */
    const CHECKOUT_URL = 'https://checkout.paddle.com';

    /**
     * The Paddle vendors URL.
     *
     * @var string
     */
    const VENDORS_URL = 'https://vendors.paddle.com';

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Indicates if Cashier migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Indicates if Cashier routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * Indicates if Cashier will mark past due subscriptions as inactive.
     *
     * @var bool
     */
    public static $deactivatePastDue = true;

    /**
     * Get the billable entity instance by its Paddle ID.
     *
     * @param  string  $paddleId
     * @return \Laravel\Paddle\Billable|null
     */
    public static function findBillable($paddleId)
    {
        if ($paddleId === null) {
            return;
        }

        $model = config('cashier.model');

        return (new $model)->where('paddle_id', $paddleId)->first();
    }

    /**
     * Get prices for a set of product ids.
     *
     * @param  array|int  $products
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public static function productPrices($products, array $options = [])
    {
        $payload = array_merge($options, [
            'product_ids' => implode(',', (array) $products),
        ]);

        $response = static::get('/prices', $payload)['response'];

        return collect($response['products'])->map(function (array $product) use ($response) {
            return new ProductPrice($response['customer_country'], $product);
        });
    }

    /**
     * Get the Paddle webhook url.
     *
     * @return string
     */
    public static function webhookUrl()
    {
        return config('cashier.webhook') ?? route('cashier.webhook');
    }

    /**
     * Perform a GET Paddle API call.
     *
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    public static function get($uri, array $payload = [])
    {
        return static::makeApiCall('get', static::CHECKOUT_URL.'/api/2.0'.$uri, $payload);
    }

    /**
     * Perform a POST Paddle API call.
     *
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    public static function post($uri, array $payload = [])
    {
        return static::makeApiCall('post', static::VENDORS_URL.'/api/2.0'.$uri, $payload);
    }

    /**
     * Perform a Paddle API call.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    protected static function makeApiCall($method, $uri, array $payload = [])
    {
        $response = Http::$method($uri, $payload);

        if ($response['success'] === false) {
            throw new PaddleException($response['error']['message'], $response['error']['code']);
        }

        return $response;
    }

    /**
     * Get the default Paddle API options.
     *
     * @param  array  $options
     * @return array
     */
    public static function paddleOptions(array $options = [])
    {
        return array_merge([
            'vendor_id' => (int) config('cashier.vendor_id'),
            'vendor_auth_code' => config('cashier.vendor_auth_code'),
        ], $options);
    }

    /**
     * Set the custom currency formatter.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public static function formatAmount($amount, $currency = null, $locale = null)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency);
        }

        $money = new Money($amount, new Currency(strtoupper($currency ?? config('cashier.currency'))));

        $locale = $locale ?? config('cashier.currency_locale');

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Configure Cashier to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }

    /**
     * Configure Cashier to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }

    /**
     * Configure Cashier to maintain past due subscriptions as active.
     *
     * @return static
     */
    public static function keepPastDueSubscriptionsActive()
    {
        static::$deactivatePastDue = false;

        return new static;
    }
}
