<?php

namespace Laravel\Paddle;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Paddle\Events\PaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionCancelled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaymentFailed;
use Laravel\Paddle\Events\SubscriptionPaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;
use Mockery\Generator\Method;

class CashierFake
{
    /**
     * The endpoints that need to be faked
     *
     * @var array
     */
    protected $endpoints;

    /**
     * The events that need to be faked
     *
     * @var array
     */
    protected $events = [
        PaymentSucceeded::class,
        SubscriptionCreated::class,
        SubscriptionCancelled::class,
        SubscriptionPaymentFailed::class,
        SubscriptionPaymentSucceeded::class,
        SubscriptionUpdated::class,
    ];

    /**
     * The payment provider to mock the user as (either "paypal" or "card")
     *
     * @var string
     */
    public static $paymentProvider = 'card';

    /**
     * Merge the provided endpoints into the default ones for mocking
     *
     * @param array $endpoints
     * @return void
     */
    public function __construct(array $endpoints = [])
    {
        foreach (array_merge(static::initialEndpoints(), $endpoints) as $endpoint => $data) {
            $this->mockEndpoint($endpoint, $data);
        }

        Event::fake($this->events);
    }

    /**
     * Syntactic sugar for the constructor
     *
     * @return static
     */
    public static function fake(array $endpoints = [])
    {
        return new static($endpoints);
    }

    /**
     * Mock a given endpoint with the provided response data
     *
     * @param string $endpoint
     * @param mixed  $data
     * @return void
     */
    public function mockEndpoint(string $endpoint, $data = [])
    {
        $response = ! is_callable($data) ? Http::response($data) : $data;

        Http::fake([
            $this->formatEndpoint($endpoint) => $data
        ]);
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
    public function card()
    {
        static::$paymentProvider = 'card';

        return $this;
    }

    /**
     * Format the given path into a full url
     *
     * @param string $path
     * @return string
     */
    protected function formatEndpoint(string $path): string
    {
        return Cashier::vendorsUrl()
               .'/api/'.static::API_VERSION
               .Str::start($path, '/');
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
     * Returns the default endpoints with their fake data.
     *
     * @return array
     */
    public static function initialEndpoints()
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
                                : [
                                    'payment_method' => 'card',
                                    'card_type' => 'visa',
                                    'last_four_digits' => '1234',
                                    'expiry_date' => '04/2022',
                                ],
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
