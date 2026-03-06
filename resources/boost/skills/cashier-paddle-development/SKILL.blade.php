---
name: cashier-paddle-development
description: "Handles Laravel Cashier Paddle integration including subscriptions, webhooks, Paddle Checkout, transactions, charges, refunds, trials, proration, pausing, and multiple subscription types. Triggered when a user mentions Cashier, Billable, paddle_id, subscribe(), checkout(), Paddle subscriptions, or billing. Also applies when setting up webhooks, debugging subscribed() returning false, handling grace periods, testing with CashierFake, or troubleshooting subscription sync issues."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Cashier Paddle Development

## When to Apply

Activate this skill when:

- Installing or configuring Laravel Cashier Paddle
- Setting up subscriptions, trials, quantities, or plan swapping
- Handling webhooks or subscription state sync issues
- Working with Paddle Checkout, transactions, or one-time charges
- Testing billing scenarios with CashierFake
- Debugging `subscribed()` returning false or subscription type mismatches

## Documentation

Use `search-docs` for detailed Cashier Paddle patterns and documentation covering subscriptions, webhooks, Paddle Checkout, transactions, payment methods, and testing.

For deeper guidance on specific topics, read the relevant reference file before implementing:

- `references/subscriptions.md` covers subscription creation, status checks, swapping, pausing, trials, quantities, and multiple products
- `references/webhooks.md` covers webhook setup, custom handlers, CSRF exclusion, and local development with reverse proxies
- `references/testing.md` covers CashierFake, API response mocking, and feature test patterns for billing code

## Basic Usage

### Installation

```bash
{{ $assist->artisanCommand('vendor:publish --tag="cashier-migrations"') }}
{{ $assist->artisanCommand('migrate') }}
{{ $assist->artisanCommand('vendor:publish --tag="cashier-config"') }}
```

### Environment Variables

```
PADDLE_CLIENT_SIDE_TOKEN=your-client-side-token
PADDLE_API_KEY=your-api-key
PADDLE_WEBHOOK_SECRET=your-webhook-secret
PADDLE_SANDBOX=true
CASHIER_CURRENCY_LOCALE=en
```

### Billable Model

@boostsnippet("Add Billable Trait", "php")
use Laravel\Paddle\Billable;

class User extends Authenticatable
{
    use Billable;
}
@endboostsnippet

If you bill a non-`User` model, add the `Billable` trait to that model as well:

@boostsnippet("Custom Billable Model", "php")
use Illuminate\Database\Eloquent\Model;
use Laravel\Paddle\Billable;

class Team extends Model
{
    use Billable;
}
@endboostsnippet

No extra Cashier registration call is required for additional billable models.

Add `@paddleJS` to your layout so Paddle's JavaScript is loaded for the overlay checkout:

@boostsnippet("Paddle JS Directive", "blade")
<head>
    @paddleJS
</head>
@endboostsnippet

### Creating a Subscription

Cashier Paddle uses a checkout-based flow. `subscribe()` returns a `Checkout` instance that is passed to a Blade component. There is no direct API call for subscription creation.

@boostsnippet("Subscription Checkout Route", "php")
Route::get('/subscribe', function (Request $request) {
    $checkout = $request->user()
        ->subscribe('pri_monthly', 'default')
        ->returnTo(route('dashboard'));

    return view('billing', ['checkout' => $checkout]);
});
@endboostsnippet

@boostsnippet("Subscription Checkout Button", "blade")
<x-paddle-button :checkout="$checkout" class="px-8 py-4">
    Subscribe
</x-paddle-button>
@endboostsnippet

For named subscriptions, pass the desired type as the second argument to `subscribe()`. Cashier stores `custom_data.subscription_type` automatically from that value; if you omit it, the local type defaults to `'default'`.

@boostsnippet("Named Subscription Checkout", "php")
$checkout = $request->user()
    ->subscribe('pri_premium_monthly', 'premium')
    ->returnTo(route('dashboard'));
@endboostsnippet

## Verification

1. Run migrations and confirm the `customers`, `subscriptions`, `subscription_items`, and `transactions` tables exist
2. Confirm `paddle/*` is excluded from CSRF protection and `PADDLE_WEBHOOK_SECRET` is set
3. Enable the required webhook event types in Paddle Dashboard > Notifications: `subscription.created`, `subscription.updated`, `subscription.paused`, `subscription.canceled`, `transaction.completed`, `transaction.updated`, `customer.updated`
4. Test webhook delivery using the Paddle Dashboard notification log. A 419 response means CSRF is blocking. A 403 response means the secret is wrong.
5. Confirm `$user->subscribed()` returns the expected value after a subscription is created

## Common Pitfalls

- `subscribed('premium')` checks the local `type` column, not the Paddle plan name. Pass the desired type as the second argument to `subscribe()`. Cashier writes `custom_data.subscription_type` automatically, so if you attach extra custom data, do not overwrite that key.
- A 419 response from the webhook endpoint means `paddle/*` is not excluded from CSRF middleware. All Paddle POST requests will be rejected until this is configured.
- A 403 response means `PADDLE_WEBHOOK_SECRET` is wrong or missing. Signature verification will silently block all events.
- `subscribed()` returns true immediately after calling `cancel()`. The subscription stays valid during the grace period. Use `onGracePeriod()` to distinguish canceled subscriptions that still have access.
- `paused()` returns false while a pause is scheduled but not yet in effect. If `paused_at` is in the future, use `onPausedGracePeriod()` instead.
- A subscription active in Paddle but missing from the app means webhooks are not reaching the controller. Check Paddle's delivery log for HTTP status codes, confirm `subscription.created` is enabled in Notifications, and verify the customer record exists locally.
- The last price on a multi-product subscription cannot be removed. Swap to a single price or cancel the subscription instead.
- Currency formatting is broken for non-English locales if the `ext-intl` PHP extension is not installed.
- Always use `search-docs` for the latest Cashier Paddle documentation rather than relying on this skill alone.
