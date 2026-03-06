# Testing Reference

Use `search-docs` for authoritative documentation on testing Cashier Paddle integrations.

## CashierFake

`Cashier::fake()` does two things at once: registers `Http::fake()` intercepts for Paddle API endpoints, and calls `Event::fake()` for all Cashier events. Call it once at the top of each test before any Cashier API interactions.

```php
use Laravel\Paddle\Cashier;

public function test_user_can_subscribe(): void
{
    Cashier::fake();

    // Seed subscription state that would normally arrive via webhook
    $user = User::factory()->create();
    $user->customer()->create(['paddle_id' => 'cus_test123']);

    $user->subscriptions()->create([
        'type'      => 'default',
        'paddle_id' => 'sub_test123',
        'status'    => 'active',
    ])->items()->create([
        'product_id' => 'pro_basic',
        'price_id'   => 'pri_monthly',
        'status'     => 'active',
        'quantity'   => 1,
    ]);

    $this->assertTrue($user->subscribed());
    $this->assertFalse($user->subscription()->onTrial());
}
```

## Mocking API Responses

When your code calls Paddle's API (e.g. `swap()`, `cancel()`, `updateQuantity()`), register a fake response before the call:

```php
Cashier::fake([
    'subscriptions/sub_test123' => [
        'data' => [
            'id'     => 'sub_test123',
            'status' => 'active',
            'items'  => [
                [
                    'price'    => ['id' => 'pri_yearly', 'product_id' => 'pro_basic'],
                    'status'   => 'active',
                    'quantity' => 1,
                ],
            ],
        ],
    ],
]);

$subscription->swap('pri_yearly');
```

The endpoint key maps to the Paddle API URI. Use glob patterns when the ID is dynamic:

```php
Cashier::fake([
    'subscriptions*' => ['data' => [...]],
]);
```

The fluent `->response()` method wraps the data in a `['data' => ...]` envelope automatically:

```php
Cashier::fake()->response('subscriptions/sub_123', [
    'id' => 'sub_123',
    'status' => 'active',
    'items' => [...],
]);
```

Simulate an API error with `->error()`, which causes `Cashier::api()` to throw `PaddleException`:

```php
Cashier::fake()->error('subscriptions/sub_bad');
```

## Asserting Events

```php
Cashier::assertSubscriptionCreated();
Cashier::assertSubscriptionUpdated();
Cashier::assertSubscriptionCanceled();
Cashier::assertSubscriptionPaused();
Cashier::assertTransactionCompleted();
Cashier::assertTransactionUpdated();
Cashier::assertCustomerUpdated();
Cashier::assertSubscriptionNotCreated();

// With a callback to check specific attributes
Cashier::assertSubscriptionCanceled(function ($event) {
    return $event->subscription->paddle_id === 'sub_test123';
});
```

## Simulating Webhooks

To test code that reacts to Cashier webhook events, post a payload directly to the webhook route. If your test environment sets `cashier.webhook_secret`, either unset it for the test or send a valid `Paddle-Signature` header.

```php
$payload = [
    'event_type' => 'transaction.completed',
    'data' => [
        'id'          => 'txn_test',
        'customer_id' => 'cus_test123',
        'status'      => 'completed',
        'custom_data' => ['order_id' => $order->id],
        'details'     => ['totals' => ['total' => '1000', 'tax' => '100']],
        'currency_code' => 'USD',
        'billed_at'   => now()->toISOString(),
    ],
];

config()->set('cashier.webhook_secret', null);

$this->postJson('paddle/webhook', $payload)->assertOk();
```

If you want to keep signature verification enabled, sign the exact JSON body you send:

```php
$payload = ['event_type' => 'transaction.completed', 'data' => [...]];
$json = json_encode($payload, JSON_THROW_ON_ERROR);
$timestamp = time();
$signature = hash_hmac('sha256', "{$timestamp}:{$json}", config('cashier.webhook_secret'));

$this->withHeader('Paddle-Signature', "ts={$timestamp};h1={$signature}")
    ->call('POST', 'paddle/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $json)
    ->assertOk();
```

## Setup Notes

- Cashier Paddle ships with `Cashier::fake()` as its primary test helper — there is no need to mock the HTTP client manually.
- The `swap()`, `cancel()`, `pause()`, and `updateQuantity()` methods all call Paddle's API. Register fake responses for these endpoints or they will attempt real network calls.
- `swap()` reads `$response['items']` to sync local subscription items via `syncSubscriptionItems()`. If the fake response omits items, `hasPrice()` will return false after the swap.
- Calling `Cashier::fake()` multiple times in a single test resets the event fake. Call it once at the test start and register all endpoint responses up front, or use the fluent `->response()` method to add responses after the initial call.
- Refer to `tests/Feature/` in the Cashier Paddle package source for integration test patterns covering subscriptions, webhooks, and transactions.
