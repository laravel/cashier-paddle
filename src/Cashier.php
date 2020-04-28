<?php

namespace Laravel\Paddle;

use Illuminate\Support\Facades\Http;
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
    const VERSION = '1.0.0-dev';

    /**
     * The Paddle base endpoint for API calls.
     *
     * @var string
     */
    const API_ENDPOINT = 'https://vendors.paddle.com/api/2.0';

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
     * Get the Paddle webhook url.
     *
     * @return string
     */
    public static function webhookUrl()
    {
        return config('cashier.webhook') ?? route('cashier.webhook');
    }

    /**
     * Perform a Paddle API call.
     *
     * @param  string  $url
     * @param  array  $options
     * @return \Illuminate\Http\Client\Response
     */
    public static function makeApiCall($url, array $options)
    {
        return Http::post(Cashier::API_ENDPOINT.$url, $options);
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
            'vendor_id' => config('cashier.vendor_id'),
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
