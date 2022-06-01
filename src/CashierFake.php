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
     * The array of callbacks for each response.
     *
     * @var array
     */
    protected $responses = [];

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

            $this->fakeHttpResponse($endpoint, array_merge([
                'success' => true,
            ], Arr::wrap($response)));
        }

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
     * Set the successful response for a given endpoint.
     *
     * @param  string  $endpoint
     * @param  mixed  $response
     * @return self
     */
    public function response(string $endpoint, $response = null)
    {
        $this->fakeHttpResponse($endpoint, [
            'success' => true,
            'response' => $response,
        ]);

        return $this;
    }

    /**
     * Set an error response for a given endpoint.
     *
     * @param  string  $endpoint
     * @param  string  $message
     * @param  int  $code
     * @return self
     *
     * @see https://developer.paddle.com/api-reference/ZG9jOjI1MzUzOTkw-api-error-codes
     */
    public function error(string $endpoint, $message = '', $code = 0)
    {
        $this->fakeHttpResponse($endpoint, [
            'success' => false,
            'error' => ['message' => $message, 'code' => $code],
        ]);

        return $this;
    }

    /**
     * Fake the given endpoint with the provided response.
     *
     * @param  string  $endpoint
     * @param  mixed  $response
     * @return void
     */
    protected function fakeHttpResponse(string $endpoint, $response)
    {
        $notFaked = ! Arr::exists($this->responses, $endpoint);

        $this->responses[$endpoint] = $response;

        if ($notFaked) {
            Http::fake([static::getFormattedVendorUrl($endpoint) => function () use ($endpoint) {
                return $this->responses[$endpoint];
            }]);
        }
    }

    /**
     * Format the given path into a full API url.
     *
     * @param  string  $path
     * @return string
     */
    public static function getFormattedVendorUrl(string $path): string
    {
        return Cashier::vendorsUrl().'/api/2.0'.Str::start($path, '/');
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
