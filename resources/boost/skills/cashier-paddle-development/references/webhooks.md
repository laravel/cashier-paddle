# Webhooks Reference

Use `search-docs` for authoritative documentation on webhooks.

## Auto-Registered Route

Cashier registers one route automatically (unless `Cashier::ignoreRoutes()` is called):

- `POST /paddle/webhook` named `cashier.webhook`

## CSRF Exclusion (Required)

Without this, Paddle's POST requests receive a 419 response and are silently dropped.

**Laravel 11+ (`bootstrap/app.php`):**

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'paddle/*',
    ]);
})
```

**Laravel 10 (`app/Http/Middleware/VerifyCsrfToken.php`):**

```php
protected $except = [
    'paddle/*',
];
```

## Webhook Signature Verification

`VerifyWebhookSignature` middleware is applied automatically when `PADDLE_WEBHOOK_SECRET` is set. If not set, verification is silently skipped — always configure it in production.

## Required Webhook Event Types

Enable these in Paddle Dashboard > Notifications:

- `customer.updated`
- `transaction.completed`
- `transaction.updated`
- `subscription.created`
- `subscription.updated`
- `subscription.paused`
- `subscription.canceled`

If `subscription.created` is missing, new subscriptions will never be recorded locally.

## Local Development

Use a reverse proxy to expose your local app to Paddle:

```bash
# Using Expose
expose share http://your-app.test

# Using Ngrok
ngrok http 80
```

Set the forwarded URL as your webhook endpoint in the Paddle Sandbox Dashboard > Notifications.

## Listening to Webhook Events

React to billing events in your application using Laravel event listeners:

```php
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Events\WebhookReceived;

// Listen to a specific Cashier event
Event::listen(TransactionCompleted::class, function (TransactionCompleted $event) {
    $orderId = $event->payload['data']['custom_data']['order_id'] ?? null;
    Order::findOrFail($orderId)->markCompleted();
});

// Listen to ALL webhooks before Cashier processes them (useful for logging)
Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    Log::info('Paddle webhook', ['type' => $event->payload['event_type']]);
});
```

All available Cashier events:

| Event | Dispatched after |
|---|---|
| `WebhookReceived` | Every incoming webhook, before processing |
| `WebhookHandled` | After Cashier processes a known event |
| `CustomerUpdated` | `customer.updated` |
| `TransactionCompleted` | `transaction.completed` |
| `TransactionUpdated` | `transaction.updated` |
| `SubscriptionCreated` | `subscription.created` |
| `SubscriptionUpdated` | `subscription.updated` |
| `SubscriptionPaused` | `subscription.paused` |
| `SubscriptionCanceled` | `subscription.canceled` |

## Extending the Webhook Controller

To override default processing or add custom behavior, extend `WebhookController`:

```php
use Laravel\Paddle\Http\Controllers\WebhookController as CashierController;

class PaddleWebhookController extends CashierController
{
    protected function handleSubscriptionCreated(array $payload): void
    {
        parent::handleSubscriptionCreated($payload);

        // Additional post-processing
        $subscriptionId = $payload['data']['id'];
        // ...
    }
}
```

Disable Cashier's auto-registered route and point to your controller:

```php
// In AppServiceProvider::boot()
Cashier::ignoreRoutes();
```

```php
// routes/web.php
Route::post('paddle/webhook', [PaddleWebhookController::class, '__invoke']);
```

## Custom Webhook URL

```env
CASHIER_WEBHOOK=https://example.com/my-paddle-webhook-url
```

## Diagnosing Webhook Issues

Check Paddle's delivery log in Dashboard > Notifications > your endpoint > Recent deliveries. Common HTTP response codes:

| Code | Cause | Fix |
|---|---|---|
| 419 | CSRF blocking the request | Exclude `paddle/*` from CSRF |
| 403 | Signature verification failed | Verify `PADDLE_WEBHOOK_SECRET` matches the dashboard |
| 404 | Route not found | Check `php artisan route:list` for `paddle/webhook` |
| 500 | Application exception | Check `storage/logs/laravel.log` |

If webhooks arrive but subscription state is stale, check that the `customers` table has a row with `paddle_id` matching the `customer_id` in the payload — `handleSubscriptionCreated` exits early if `findBillable()` returns null.
