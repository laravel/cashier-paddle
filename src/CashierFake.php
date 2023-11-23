<?php

namespace Laravel\Paddle;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Paddle\Events\CustomerUpdated;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaused;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Events\TransactionUpdated;

/**
 * @todo
 */
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
        foreach ($endpoints as $endpoint => $response) {
            if (! Arr::isAssoc($endpoints)) {
                $endpoint = $response;
                $response = null;
            }

            $this->fakeHttpResponse($endpoint, Arr::wrap($response));
        }

        Event::fake(array_merge([
            CustomerUpdated::class,
            TransactionCompleted::class,
            TransactionUpdated::class,
            SubscriptionCreated::class,
            SubscriptionUpdated::class,
            SubscriptionCanceled::class,
            SubscriptionPaused::class,
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
     * @param  array  $data
     * @return self
     */
    public function response(string $endpoint, array $data)
    {
        $this->fakeHttpResponse($endpoint, [
            'data' => $data,
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
            'error' => ['detail' => $message],
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
            Http::fake([static::getFormattedApiUrl($endpoint) => function () use ($endpoint) {
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
    public static function getFormattedApiUrl(string $path): string
    {
        return Cashier::apiUrl().Str::start($path, '/');
    }

    /**
     * Assert if the CustomerUpdated event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertCustomerUpdated($callback = null)
    {
        Event::assertDispatched(CustomerUpdated::class, $callback);
    }

    /**
     * Assert if the TransactionCompleted event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertTransactionCompleted($callback = null)
    {
        Event::assertDispatched(TransactionCompleted::class, $callback);
    }

    /**
     * Assert if the TransactionUpdated event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertTransactionUpdated($callback = null)
    {
        Event::assertDispatched(TransactionUpdated::class, $callback);
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
     * Assert if the SubscriptionCanceled event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionCanceled($callback = null)
    {
        Event::assertDispatched(SubscriptionCanceled::class, $callback);
    }

    /**
     * Assert if the SubscriptionPaused event was dispatched based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public static function assertSubscriptionPaused($callback = null)
    {
        Event::assertDispatched(SubscriptionPaused::class, $callback);
    }
}
