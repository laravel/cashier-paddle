<?php

namespace Laravel\Paddle;

use Exception;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Exceptions\PaddleException;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class Cashier
{
    const VERSION = '2.1.0';

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Indicates if Cashier routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * Indicates if Cashier will mark past due subscriptions as invalid.
     *
     * @var bool
     */
    public static $deactivatePastDue = true;

    /**
     * The customer model class name.
     *
     * @var string
     */
    public static $customerModel = Customer::class;

    /**
     * The subscription model class name.
     *
     * @var string
     */
    public static $subscriptionModel = Subscription::class;

    /**
     * The subscription item model class name.
     *
     * @var string
     */
    public static $subscriptionItemModel = SubscriptionItem::class;

    /**
     * The transaction model class name.
     *
     * @var string
     */
    public static $transactionModel = Transaction::class;

    /**
     * Preview prices for a given set of items.
     *
     * @param  array|string  $items
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public static function previewPrices($items, array $options = [])
    {
        $items = static::api('POST', 'pricing-preview', array_merge([
            'items' => static::normalizeItems($items),
        ], $options))['data']['details']['line_items'];

        return collect($items)->map(function (array $item) {
            return new PricePreview($item);
        });
    }

    /**
     * Get the customer instance by its Paddle customer ID.
     *
     * @param  string  $customerId
     * @return \Laravel\Paddle\Billable|null
     */
    public static function findBillable($customerId)
    {
        return (new static::$customerModel)->where('paddle_id', $customerId)->first()?->billable;
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
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $payload
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Laravel\Paddle\Exceptions\PaddleException
     */
    public static function api($method, $uri, array $payload = [])
    {
        if (empty($apiKey = config('cashier.api_key', config('cashier.auth_code')))) {
            throw new Exception('Paddle API key not set.');
        }

        $host = static::apiUrl();

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($apiKey)
            ->withUserAgent('Laravel\Paddle/'.static::VERSION)
            ->withHeaders(['Paddle-Version' => 1])
            ->$method("{$host}/{$uri}", $payload);

        if (isset($response['error'])) {
            $message = "Paddle API error '{$response['error']['detail']}' occurred";

            if (isset($response['error']['errors'])) {
                $message .= ' with validation errors ('.json_encode($response['error']['errors']).')';
            }

            throw new PaddleException($response['error']['detail']);
        }

        return $response;
    }

    /**
     * Get the Paddle API url.
     *
     * @return string
     */
    public static function apiUrl()
    {
        return 'https://'.(config('cashier.sandbox') ? 'sandbox-' : '').'api.paddle.com';
    }

    /**
     * Normalize the given items to a Paddle accepted format.
     *
     * @param  array|string  $items
     * @param  string  $priceKey
     * @return array
     */
    public static function normalizeItems($items, string $priceKey = 'price_id'): array
    {
        return collect($items)->map(function ($item, $key) use ($priceKey) {
            if (is_array($item)) {
                return $item;
            }

            if (is_string($key)) {
                return [
                    $priceKey => $key,
                    'quantity' => $item,
                ];
            }

            return [
                $priceKey => $item,
                'quantity' => 1,
            ];
        })->values()->all();
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
     * @param  string  $currency
     * @param  string|null  $locale
     * @param  array  $options
     * @return string
     */
    public static function formatAmount($amount, $currency, $locale = null, array $options = [])
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency, $locale, $options);
        }

        $money = new Money($amount, new Currency(strtoupper($currency)));

        $locale = $locale ?? config('cashier.currency_locale');

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        if (isset($options['min_fraction_digits'])) {
            $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['min_fraction_digits']);
        }

        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
    }

    /**
     * Determine if the given currency uses cents.
     *
     * @param  \Money\Currency  $currency
     * @return bool
     */
    public static function currencyUsesCents(Currency $currency)
    {
        return ! in_array($currency->getCode(), ['JPY', 'KRW'], true);
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

    /**
     * Set the customer model class name.
     *
     * @param  string  $customerModel
     * @return void
     */
    public static function useCustomerModel($customerModel)
    {
        static::$customerModel = $customerModel;
    }

    /**
     * Set the subscription model class name.
     *
     * @param  string  $subscriptionModel
     * @return void
     */
    public static function useSubscriptionModel($subscriptionModel)
    {
        static::$subscriptionModel = $subscriptionModel;
    }

    /**
     * Set the subscription item model class name.
     *
     * @param  string  $subscriptionItemModel
     * @return void
     */
    public static function useSubscriptionItemModel($subscriptionItemModel)
    {
        static::$subscriptionItemModel = $subscriptionItemModel;
    }

    /**
     * Set the transaction model class name.
     *
     * @param  string  $transactionModel
     * @return void
     */
    public static function useTransactionModel($transactionModel)
    {
        static::$transactionModel = $transactionModel;
    }

    /**
     * Create a fake Cashier instance.
     *
     * @return \Laravel\Paddle\CashierFake
     */
    public static function fake(...$arguments)
    {
        return CashierFake::fake(...$arguments);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertCustomerUpdated($callback = null)
    {
        CashierFake::assertCustomerUpdated($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertTransactionCompleted($callback = null)
    {
        CashierFake::assertTransactionCompleted($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertTransactionUpdated($callback = null)
    {
        CashierFake::assertTransactionUpdated($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionCreated($callback = null)
    {
        CashierFake::assertSubscriptionCreated($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionNotCreated($callback = null)
    {
        CashierFake::assertSubscriptionNotCreated($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionUpdated($callback = null)
    {
        CashierFake::assertSubscriptionUpdated($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionCanceled($callback = null)
    {
        CashierFake::assertSubscriptionCanceled($callback);
    }

    /**
     * Pass-thru to the CashierFake method of the same name.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionPaused($callback = null)
    {
        CashierFake::assertSubscriptionPaused($callback);
    }
}
