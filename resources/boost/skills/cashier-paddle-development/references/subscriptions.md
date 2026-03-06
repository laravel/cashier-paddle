# Subscriptions Reference

Use `search-docs` for authoritative documentation on subscriptions.

## Status Methods

| Method | Returns true when |
|---|---|
| `$user->subscribed('default')` | Active, trialing, or on grace period |
| `->onTrial()` | Trial period active |
| `->onGracePeriod()` | Canceled, billing period not yet ended |
| `->canceled()` | Status is `canceled` |
| `->recurring()` | Active and not on trial |
| `->pastDue()` | Payment overdue |
| `->paused()` | `paused_at` is in the past |
| `->onPausedGracePeriod()` | `paused_at` is set but in the future |

Check by price or product:

```php
$user->subscribedToPrice('pri_monthly', 'default');
$user->subscribedToProduct('pro_premium', 'default');
```

Query scopes mirror all instance methods:

```php
Subscription::query()->active();
Subscription::query()->onTrial();
Subscription::query()->canceled();
Subscription::query()->paused();
// etc.
```

## Swapping Plans

```php
$user->subscription()->swap('pri_yearly');                  // with proration
$user->subscription()->noProrate()->swap('pri_yearly');     // no proration
$user->subscription()->swapAndInvoice('pri_yearly');        // swap + immediate invoice
$user->subscription()->doNotBill()->swap('pri_yearly');     // swap, no charge
```

## Quantity

```php
$user->subscription()->incrementQuantity();
$user->subscription()->decrementQuantity();
$user->subscription()->updateQuantity(10);
$user->subscription()->noProrate()->updateQuantity(10);

// For a specific price in a multi-product subscription
$user->subscription()->incrementQuantity(1, 'pri_addon');
$user->subscription()->updateQuantity(3, 'pri_addon');
```

## Trials

Trials with payment upfront are configured on the price in the Paddle dashboard. For generic trials (no payment required):

```php
$user->createAsCustomer(['trial_ends_at' => now()->addDays(14)]);

$user->onTrial();         // true
$user->onGenericTrial();  // true
$user->trialEndsAt();     // Carbon instance
```

Extend or activate a trial:

```php
$user->subscription()->extendTrial(now()->addDays(7));
$user->subscription()->activate(); // end trial immediately, begin billing
```

## Multiple Products on One Subscription

```php
// Subscribe to multiple prices
$checkout = $user->subscribe(['pri_base', 'pri_addon']);

// With quantities
$checkout = $user->subscribe(['pri_base', 'pri_addon' => 5]);

// Add a product by swapping (include all prices you want to keep)
$user->subscription()->swap(['pri_base', 'pri_addon']);

// Remove a product (must keep at least one price)
$user->subscription()->swap(['pri_base']);

// Check if a specific price is on the subscription
$user->subscription()->hasPrice('pri_addon');
$user->subscription()->findItemOrFail('pri_addon');
```

## Multiple Named Subscriptions

```php
// Create named subscriptions
$checkout = $user->subscribe('pri_gym', 'gym')
    ->returnTo(route('home'));

// Access them independently
$user->subscription('gym')->swap('pri_gym_yearly');
$user->subscription('gym')->cancel();
$user->subscribed('gym');
```

Cashier sets `custom_data.subscription_type` from the second argument to `subscribe()` automatically. If you call `customData()` for other metadata, do not overwrite that key.

## Pausing

```php
$user->subscription()->pause();                              // at next billing cycle
$user->subscription()->pauseNow();                           // immediately
$user->subscription()->pauseUntil(now()->addMonth());
$user->subscription()->pauseNowUntil(now()->addMonth());
$user->subscription()->resume();
```

## Cancellation

```php
$user->subscription()->cancel();          // at end of billing period
$user->subscription()->cancelNow();       // immediately
$user->subscription()->stopCancelation(); // undo a scheduled cancel
```

## Single Charges on a Subscription

```php
$user->subscription()->charge('pri_addon');             // at next billing cycle
$user->subscription()->chargeAndInvoice('pri_addon');   // immediately
```

## Payment Method Update

```php
return $user->subscription()->redirectToUpdatePaymentMethod();
```

## Past and Upcoming Payments

```php
$subscription = $user->subscription();

$lastPayment = $subscription->lastPayment();
$nextPayment = $subscription->nextPayment();

// Display
"Next payment: {$nextPayment->amount()} due on {$nextPayment->date()->format('d/m/Y')}";
```
