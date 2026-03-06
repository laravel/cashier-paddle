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

For a non-User model, register it in a service provider:

@boostsnippet("Custom Billable Model", "php")
// In AppServiceProvider::boot()
Cashier::useCustomerModel(Team::class);
@endboostsnippet

Add `@paddleJS` to your layout so Paddle's JavaScript is loaded for the overlay checkout:

@boostsnippet("Paddle JS Directive", "blade")
<head>
    @paddleJS
</head>
@endboostsnippet

### Creating a Subscription

Cashier Paddle uses a checkout-based flow. `subscribe()` returns a `Checkout` instance that is passed to a Blade component — there is no direct API call for subscription creation.

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

For **named subscriptions** (e.g. a user can hold multiple subscription types), pass `subscription_type` as custom data. Cashier reads this from the webhook to set the local `type` column. Without it, every subscription defaults to `'default'`.

@boostsnippet("Named Subscription with Custom Data", "php")
$checkout = $request->user()
    ->subscribe('pri_premium_monthly', 'premium')
    ->customData(['subscription_type' => 'premium'])
    ->returnTo(route('dashboard'));
@endboostsnippet

## Verification

1. Run migrations and confirm the `customers`, `subscriptions`, `subscription_items`, and `transactions` tables exist
2. Confirm `paddle/*` is excluded from CSRF protection and `PADDLE_WEBHOOK_SECRET` is set
3. Enable the required webhook event types in Paddle Dashboard > Notifications: `subscription.created`, `subscription.updated`, `subscription.paused`, `subscription.canceled`, `transaction.completed`, `transaction.updated`, `customer.updated`
4. Test webhook delivery using the Paddle Dashboard notification log — a 419 means CSRF is blocking, a 403 means the secret is wrong
5. Confirm `$user->subscribed()` returns the expected value after a subscription is created

## Common Pitfalls

- `subscribed('premium')` returns false even though the user subscribed — the argument is the local subscription `type`, not the Paddle plan name. The `type` is set from `custom_data.subscription_type` in the webhook. If `customData(['subscription_type' => 'premium'])` was not passed during checkout, every subscription is stored as `'default'`.
- Webhooks returning 419 — the `paddle/*` route is not excluded from CSRF middleware. Without this exclusion, all Paddle POST requests are rejected.
- Webhooks returning 403 — `PADDLE_WEBHOOK_SECRET` is wrong or not set. Without a matching secret, signature verification fails silently and blocks all events.
- `subscribed()` returns true after calling `cancel()` — expected. The subscription stays valid during the grace period. Use `onGracePeriod()` to distinguish.
- `paused()` returns false even though pause was requested — if `paused_at` is set but in the future, the subscription is on a pause grace period. Use `onPausedGracePeriod()` to check.
- Subscription active in Paddle but not in app — webhooks are not reaching the controller. Check Paddle's delivery log for HTTP status codes, confirm `subscription.created` is enabled in Notifications, and verify the customer record exists locally.
- Cannot remove the last price from a multi-product subscription — swap to a single price or cancel instead.
- Currency formatting broken for non-English locales — install the `ext-intl` PHP extension.
- Always use `search-docs` for the latest Cashier Paddle documentation rather than relying on this skill alone.
