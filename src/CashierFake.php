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
     * The payment provider to mock the user as (either "paypal" or "card")
     *
     * @var string
     */
    protected static $paymentProvider = 'card';

    protected static $cardData = [
        'card_type' => 'visa',
        'last_four_digits' => '1234',
        'expiry_date' => '04/2022',
    ];

    /**
     * Initialize the fake instance
     *
     * @param array         $endpoints
     * @param string|array  $events
     * @return void
     */
    public function __construct(array $endpoints = [], $events = [])
    {
        // Merge user provided endpoints with our initial ones for mocking
        foreach (array_merge($this->endpoints(), $endpoints) as $endpoint => $data) {
            $this->mockEndpoint($endpoint, $data);
        }

        // Merge user provided events and mock
        Event::fake(array_merge([
            PaymentSucceeded::class,
            SubscriptionCreated::class,
            SubscriptionCancelled::class,
            SubscriptionPaymentFailed::class,
            SubscriptionPaymentSucceeded::class,
            SubscriptionUpdated::class,
        ], Arr::wrap($events)));
    }

    /**
     * Static constructor for syntactic sugar
     *
     * @return static
     */
    public static function fake(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Mock the user as if they used PayPal as a payment provider
     *
     * @return self
     */
    public function paypal()
    {
        static::$paymentProvider = 'paypal';

        return $this;
    }

    /**
     * Mock the user as if they used a credit card as a payment provider
     *
     * @return self
     */
    public function card($data = [])
    {
        static::$paymentProvider = 'card';
        static::$cardData = array_merge(static::$cardData, $data);

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
     * Format the given path into a full url
     *
     * @param string $path
     * @return string
     */
    public static function retrieveEndpoint(string $path): string
    {
        return Cashier::vendorsUrl()
               .'/api/'.static::API_VERSION
               .Str::start($path, '/');
    }

    /**
     * Mock a given endpoint with the provided response data
     *
     * @param string $endpoint
     * @param mixed  $data
     * @return void
     */
    protected function mockEndpoint(string $endpoint, $data = [])
    {
        $response = is_array($data) ? Http::response($data) : $data;

        Http::fake([
            static::retrieveEndpoint($endpoint) => $data
        ]);
    }

    /**
     * Returns the default endpoints with their fake data.
     *
     * @return array
     */
    protected function endpoints()
    {
        return [
            static::PATH_PAYMENT_REFUND => [
                'success' => true,
                'response' => [
                    'refund_request_id' => 12345,
                ],
            ],

            static::PATH_SUBSCRIPTION_USERS => function () {
                return [
                    'success' => true,
                    'response' => [
                        [
                            'subscription_id' => 3423423,
                            'user_email' => 'john@example.com',
                            'payment_information' => static::$paymentProvider === 'paypal'
                                ? ['payment_method' => 'paypal']
                                : array_merge(['payment_method' => 'card'], static::$cardData),
                            'last_payment' => [
                                'amount' => 0.00,
                                'currency' => 'EUR',
                                'date' => '',
                            ],
                        ]
                    ]
                ];
            },

            static::PATH_SUBSCRIPTION_MODIFIERS => [
                'success' => true,
                'response' => [
                    [
                        'modifier_id' => 6789,
                        'sucscription_id' => 3423423,
                        'amount' => 15.00,
                        'currency' => 'EUR',
                        'is_recurring' => false,
                        'description' => 'This is a test modifier'
                    ]
                ]
            ],

            static::PATH_SUBSCRIPTION_MODIFIERS_CREATE => [
                'success' => true,
                'response' => [
                    'subscription_id' => 3423423,
                    'modifier_id' => 6789,
                ],
            ],

            static::PATH_SUBSCRIPTION_MODIFIERS_DELETE => [
                'success' => true
            ],
        ];
    }

    const API_VERSION = '2.0';
    const PATH_PAYMENT_REFUND = 'payment/refund';
    const PATH_SUBSCRIPTION_USERS = 'subscription/users';
    const PATH_SUBSCRIPTION_MODIFIERS = 'subscription/modifiers';
    const PATH_SUBSCRIPTION_MODIFIERS_CREATE = 'subscription/modifiers/create';
    const PATH_SUBSCRIPTION_MODIFIERS_DELETE = 'subscription/modifiers/delete';
}
