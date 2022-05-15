<?php

namespace Laravel\Paddle;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Paddle\Events\PaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionCancelled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaymentFailed;
use Laravel\Paddle\Events\SubscriptionPaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionUpdated;

class CashierFake
{
    /**
     * The payment information to mock.
     *
     * @var string
     */
    protected static $paymentInformation = [
        'payment_method' => 'card',
        'card_type' => 'visa',
        'last_four_digits' => '1234',
        'expiry_date' => '04/2022',
    ];

    /**
     * Initialize the fake instance and fake Cashier's events and API calls.
     *
     * @param  array  $endpoints
     * @param  string|array  $events
     * @return void
     */
    public function __construct(array $endpoints = [], $events = [])
    {
        foreach (array_merge($this->defaultVendorEndpoints(), $endpoints) as $endpoint => $response) {
            Http::fake([static::getFormattedVendorUrl($endpoint) => $response]);
        }

        // Merge user provided events and mock
        Event::fake(array_merge([
            PaymentSucceeded::class,
            SubscriptionCreated::class,
            SubscriptionUpdated::class,
            SubscriptionCancelled::class,
            SubscriptionPaymentFailed::class,
            SubscriptionPaymentSucceeded::class,
        ], Arr::wrap($events)));
    }

    /**
     * Syntactic sugar for the constructor.
     *
     * @return static
     */
    public static function fake(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Mock the user as if they used PayPal as a payment method.
     *
     * @param  array  $paymentInformation
     * @return self
     */
    public function paypal(array $paymentInformation = [])
    {
        static::$paymentInformation = array_merge([
            'payment_method' => 'paypal'
        ], $paymentInformation);

        return $this;
    }

    /**
     * Mock the user as if they used a credit card as a payment method.
     *
     * @param  array  $paymentInformation
     * @return self
     */
    public function card(array $paymentInformation = [])
    {
        static::$paymentInformation = array_merge([
            'payment_method' => 'card',
            'card_type' => 'visa',
            'last_four_digits' => '1234',
            'expiry_date' => '04/2022',
        ], $paymentInformation);

        return $this;
    }

    /**
     * Assert if the PaymentSucceeded event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertPaymentSucceeded($callback = null)
    {
        Event::assertDispatched(PaymentSucceeded::class, $callback);
    }

    /**
     * Assert if the SubscriptionPaymentSucceeded event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionPaymentSucceeded($callback = null)
    {
        Event::assertDispatched(SubscriptionPaymentSucceeded::class, $callback);
    }

    /**
     * Assert if the SubscriptionPaymentFailed event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionPaymentFailed($callback = null)
    {
        Event::assertDispatched(SubscriptionPaymentFailed::class, $callback);
    }

    /**
     * Assert if the SubscriptionCreated event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionCreated($callback = null)
    {
        Event::assertDispatched(SubscriptionCreated::class, $callback);
    }

    /**
     * Assert if the SubscriptionCreated event was not dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionNotCreated($callback = null)
    {
        Event::assertNotDispatched(SubscriptionCreated::class, $callback);
    }

    /**
     * Assert if the SubscriptionUpdated event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionUpdated($callback = null)
    {
        Event::assertDispatched(SubscriptionUpdated::class, $callback);
    }

    /**
     * Assert if the SubscriptionCancelled event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionCancelled($callback = null)
    {
        Event::assertDispatched(SubscriptionCancelled::class, $callback);
    }

    /**
     * Format the given path into a full API url.
     *
     * @param  string  $path
     * @return string
     */
    public static function getFormattedVendorUrl(string $path): string
    {
        return Cashier::vendorsUrl()
               .'/api/2.0'
               .Str::start($path, '/');
    }

    /**
     * Returns the default endpoints and their faked responses.
     *
     * @return array
     */
    protected function defaultVendorEndpoints()
    {
        return [
            'payment/refund' => [
                'success' => true,
                'response' => [
                    'refund_request_id' => 12345,
                ],
            ],

            'subscription/users' => function () {
                return [
                    'success' => true,
                    'response' => [
                        [
                            'subscription_id' => 3423423,
                            'user_email' => 'john@example.com',
                            'payment_information' => static::$paymentInformation,
                            'last_payment' => [
                                'amount' => 0.00,
                                'currency' => 'EUR',
                                'date' => '',
                            ],
                        ],
                    ],
                ];
            },

            'subscription/modifiers' => [
                'success' => true,
                'response' => [
                    [
                        'modifier_id' => 6789,
                        'sucscription_id' => 3423423,
                        'amount' => 15.00,
                        'currency' => 'EUR',
                        'is_recurring' => false,
                        'description' => 'This is a test modifier',
                    ],
                ],
            ],

            'subscription/modifiers/create' => [
                'success' => true,
                'response' => [
                    'subscription_id' => 3423423,
                    'modifier_id' => 6789,
                ],
            ],

            'subscription/modifiers/delete' => [
                'success' => true,
            ],
        ];
    }
}
