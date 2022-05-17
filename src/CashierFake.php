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
     * Initialize the fake instance and fake Cashier's events and API calls.
     *
     * @param  array  $endpoints
     * @param  string|array  $events
     * @return void
     */
    public function __construct(array $endpoints = [], $events = [])
    {
        foreach (
            $endpoints = array_merge([
                'payment/refund',
                'subscription/users',
                'subscription/modifiers',
                'subscription/modifiers/create',
                'subscription/modifiers/delete',
            ], $endpoints)
            as $endpoint => $response
        ) {
            if (! Arr::isAssoc($endpoints)) {
                $endpoint = $response;
                $response = null;
            }

            Http::fake([
                static::getFormattedVendorUrl($endpoint) => array_merge(['success' => true], Arr::wrap($response)),
            ]);
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
}
