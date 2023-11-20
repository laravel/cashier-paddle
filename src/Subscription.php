<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laravel\Paddle\Concerns\Prorates;
use LogicException;

/**
 * @property \Laravel\Paddle\Billable $billable
 */
class Subscription extends Model
{
    use Prorates;

    const STATUS_ACTIVE = 'active';
    const STATUS_TRIALING = 'trialing';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELED = 'canceled';

    const DEFAULT_TYPE = 'default';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['items'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'paused_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the billable model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Get the subscription items related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Cashier::$subscriptionItemModel);
    }

    /**
     * Get the subscription item for the given price.
     *
     * @param  string  $price
     * @return \Laravel\Paddle\SubscriptionItem
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findItemOrFail($price)
    {
        return $this->items()->where('price_id', $price)->firstOrFail();
    }

    /**
     * Retrieve a specific item by price or the single item on a subscription.
     *
     * @param  string|null  $price
     * @return \Laravel\Paddle\SubscriptionItem
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    protected function singleItemOrFail($price = null)
    {
        if ($this->items()->count() > 1 && is_null($price)) {
            throw new InvalidArgumentException(
                'Please provide a price when retrieving an item of a subscription with multiple prices.'
            );
        }

        return $price ? $this->findItemOrFail($price) : $this->items()->firstOrFail();
    }

    /**
     * Get all of the transactions for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Cashier::$transactionModel, 'paddle_subscription_id', 'paddle_id')
            ->orderByDesc('created_at');
    }

    /**
     * Determine if the subscription has multiple prices.
     *
     * @return bool
     */
    public function hasMultiplePrices()
    {
        return $this->items->count() > 1;
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function hasSinglePrice()
    {
        return ! $this->hasMultiplePrices();
    }

    /**
     * Determine if the subscription has a specific product.
     *
     * @param  string  $product
     * @return bool
     */
    public function hasProduct($product)
    {
        return $this->items->contains(function (SubscriptionItem $item) use ($product) {
            return $item->product_id === $product;
        });
    }

    /**
     * Determine if the subscription has a specific price.
     *
     * @param  string  $price
     * @return bool
     */
    public function hasPrice($price)
    {
        return $this->items->contains(function (SubscriptionItem $item) use ($price) {
            return $item->price_id === $price;
        });
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->onTrial() || $this->active() || (! Cashier::$deactivatePastDue && $this->pastDue());
    }

    /**
     * Filter query by valid.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeValid($query)
    {
        $query->where('status', self::STATUS_TRIALING)
            ->orWhere('status', self::STATUS_ACTIVE);

        if (! Cashier::$deactivatePastDue) {
            $query->orWhere('status', self::STATUS_PAST_DUE);
        }
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        return $this->status === self::STATUS_TRIALING;
    }

    /**
     * Filter query by on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnTrial($query)
    {
        $query->where('status', self::STATUS_TRIALING);
    }

    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function hasExpiredTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Filter query by expired trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeExpiredTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

    /**
     * Filter query by not on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnTrial($query)
    {
        $query->where('status', '!=', self::STATUS_TRIALING);
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Filter query by active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeActive($query)
    {
        $query->where('status', '=', self::STATUS_ACTIVE);
    }

    /**
     * Determine if the subscription is active and not on any grace period.
     *
     * @return bool
     */
    public function recurring()
    {
        return $this->active() && ! $this->onPausedGracePeriod() && ! $this->onGracePeriod();
    }

    /**
     * Filter query by recurring.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeRecurring($query)
    {
        $query->active()->notOnPausedGracePeriod()->notOnGracePeriod();
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Filter query by past due.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePastDue($query)
    {
        $query->where('status', self::STATUS_PAST_DUE);
    }

    /**
     * Determine if the subscription is paused.
     *
     * @return bool
     */
    public function paused()
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Filter query by paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePaused($query)
    {
        $query->where('status', self::STATUS_PAUSED);
    }

    /**
     * Filter query by not paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotPaused($query)
    {
        $query->where('status', '!=', self::STATUS_PAUSED);
    }

    /**
     * Determine if the subscription is within its grace period after being paused.
     *
     * @return bool
     */
    public function onPausedGracePeriod()
    {
        return $this->paused_at && $this->paused_at->isFuture();
    }

    /**
     * Filter query by on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnPausedGracePeriod($query)
    {
        $query->whereNotNull('paused_at')->where('paused_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnPausedGracePeriod($query)
    {
        $query->whereNull('paused_at')->orWhere('paused_at', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Filter query by canceled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeCanceled($query)
    {
        $query->where('status', self::STATUS_CANCELED);
    }

    /**
     * Filter query by not canceled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotCanceled($query)
    {
        $query->where('status', '!=', self::STATUS_CANCELED);
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Filter query by on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnGracePeriod($query)
    {
        $query->whereNotNull('ends_at')->where('ends_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnGracePeriod($query)
    {
        $query->whereNull('ends_at')->orWhere('ends_at', '<=', Carbon::now());
    }

    /**
     * Bill for one-time charges on top of the subscription.
     *
     * @param  string|array  $items
     * @param  bool  $chargeNow
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function charge($items, bool $chargeNow = false)
    {
        if (empty($items = (array) $items)) {
            throw new InvalidArgumentException('Please provide at least one item when charging one-time.');
        }

        $response = Cashier::api('POST', "subscriptions/{$this->paddle_id}/charge", [
            'effective_from' => $chargeNow ? 'immediately' : 'next_billing_period',
            'items' => Cashier::normalizeItems($items),
        ])['data'];

        $this->forceFill([
            'status' => $response['status'],
        ])->save();

        return $this;
    }

    /**
     * Bill for one-time charges on top of the subscription, and invoice immediately.
     *
     * @param  string|array  $items
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function chargeAndInvoice($items)
    {
        $this->setProrateAndInvoice('chargeAndInvoice');

        return $this->charge($items, true);
    }

    /**
     * Increment the quantity of a subscription item.
     *
     * @param  int  $count
     * @param  string|null  $price
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function incrementQuantity($count = 1, $price = null)
    {
        $item = $this->singleItemOrFail($price);

        return $this->updateQuantity($item->quantity + $count, $item);
    }

    /**
     * Increment the quantity of the subscription, and invoice immediately.
     *
     * @param  int  $count
     * @param  string|null  $price
     * @return $this
     */
    public function incrementAndInvoice($count = 1, $price = null)
    {
        $item = $this->singleItemOrFail($price);

        $this->setProrateAndInvoice('incrementAndInvoice');

        return $this->updateQuantity($item->quantity + $count, $item);
    }

    /**
     * Decrement the quantity of a subscription item.
     *
     * @param  int  $count
     * @param  string|null  $price
     * @return $this
     */
    public function decrementQuantity($count = 1, $price = null)
    {
        $item = $this->singleItemOrFail($price);

        return $this->updateQuantity(max(1, $item->quantity - $count));
    }

    /**
     * Update the quantity of the subscription.
     *
     * @param  int  $quantity
     * @param  \Laravel\Paddle\SubscriptionItem|string|null  $price
     * @return $this
     */
    public function updateQuantity($quantity, $price = null)
    {
        if ($quantity < 1) {
            throw new LogicException('Quantities of zero are not allowed.');
        }

        $itemToUpdate = $price instanceof SubscriptionItem ? $price : $this->singleItemOrFail($price);

        $items = $this->items()->get(['quantity', 'price_id'])->pluck(['quantity', 'price_id'])->toArray();

        foreach ($items as $key => $item) {
            if ($item['price_id'] === $itemToUpdate->price_id) {
                $items[$key]['quantity'] = $quantity;
            }
        }

        $response = $this->updatePaddleSubscription([
            'items' => $items,
            'proration_billing_mode' => $this->prorationBehavior,
        ]);

        $this->forceFill([
            'status' => $response['status'],
        ])->save();

        $itemToUpdate->forceFill([
            'quantity' => $quantity,
        ])->save();

        $this->load('items');

        return $this;
    }

    /**
     * Extend the trial period of the subscription.
     *
     * @param  \DateTimeInterface|string  $until
     * @return $this
     */
    public function extendTrial($until)
    {
        $response = $this->updatePaddleSubscription([
            'next_billed_at' => Carbon::parse($until)->format(DateTimeInterface::RFC3339),
            'proration_billing_mode' => 'do_not_bill',
        ]);

        $this->forceFill([
            'status' => $response['status'],
            'trial_ends_at' => Carbon::parse($response['next_billed_at'], 'UTC'),
        ])->save();

        $this->syncSubscriptionItems($response['items']);

        return $this;
    }

    /**
     * Force the trial to end immediately and activate the subscription.
     *
     * @return $this
     */
    public function activate()
    {
        $response = Cashier::api('POST', "subscriptions/{$this->paddle_id}/activate")['data'];

        $this->forceFill([
            'status' => $response['status'],
            'trial_ends_at' => null,
        ])->save();

        $this->syncSubscriptionItems($response['items']);

        return $this;
    }

    /**
     * Swap the subscription to new Paddle items.
     *
     * @param  string|array  $items
     * @param  array  $options
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function swap($items, array $options = [])
    {
        if (empty($items = (array) $items)) {
            throw new InvalidArgumentException('Please provide at least one item when swapping.');
        }

        $items = Cashier::normalizeItems($items);

        $response = $this->updatePaddleSubscription(array_merge($options, [
            'items' => $items,
            'proration_billing_mode' => $this->prorationBehavior,
        ]));

        $this->forceFill([
            'status' => $response['status'],
        ])->save();

        $this->syncSubscriptionItems($response['items']);

        return $this;
    }

    /**
     * Swap the subscription to a new Paddle plan, and invoice immediately.
     *
     * @param  string|array  $items
     * @param  array  $options
     * @return $this
     */
    public function swapAndInvoice($items, array $options = [])
    {
        $this->setProrateAndInvoice('swapAndInvoice');

        return $this->swap($items, $options);
    }

    /**
     * Change the billing cycle anchor.
     *
     * @param  \DateTimeInterface|string|null  $date
     * @return $this
     */
    public function anchorBillingCycleOn($date)
    {
        $this->updatePaddleSubscription([
            'next_billed_at' => Carbon::parse($date)->format(DateTimeInterface::RFC3339),
            'proration_billing_mode' => $this->prorationBehavior,
        ]);

        return $this;
    }

    /**
     * Get the Paddle payment method update url.
     *
     * @return string
     */
    public function paymentMethodUpdateUrl()
    {
        return Cashier::api('GET', "subscriptions/{$this->paddle_id}")['data']['management_urls']['update_payment_method'];
    }

    /**
     * Redirect the user to the Paddle payment method update url.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToUpdatePaymentMethod()
    {
        return redirect($this->paymentMethodUpdateUrl());
    }

    /**
     * Get the Paddle cancel url.
     *
     * @return string
     */
    public function cancelUrl()
    {
        return Cashier::api('GET', "subscriptions/{$this->paddle_id}")['data']['management_urls']['cancel'];
    }

    /**
     * Redirect the user to the Paddle cancel url.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToCancel()
    {
        return redirect($this->paymentMethodUpdateUrl());
    }

    /**
     * Pause the subscription.
     *
     * @param  bool  $pauseNow
     * @param  \DateTimeInterface|string|null  $until
     * @return $this
     */
    public function pause(bool $pauseNow = false, $until = null)
    {
        $response = Cashier::api('POST', "subscriptions/{$this->paddle_id}/pause", [
            'effective_from' => $pauseNow ? 'immediately' : 'next_billing_period',
            'resume_at' => $until ? Carbon::parse($until)->format(DateTimeInterface::RFC3339) : null,
        ])['data'];

        $this->forceFill([
            'status' => $response['status'],
            'paused_at' => Carbon::parse($response['paused_at'], 'UTC'),
        ])->save();

        $this->syncSubscriptionItems($response['items']);

        return $this;
    }

    /**
     * Pause the subscription until a certain date.
     *
     * @param  \DateTimeInterface|string  $until
     * @return $this
     */
    public function pauseUntil($until)
    {
        return $this->pause(false, $until);
    }

    /**
     * Pause the subscription immediately.
     *
     * @return $this
     */
    public function pauseNow()
    {
        return $this->pause(true);
    }

    /**
     * Pause the subscription immediately and until a certain date.
     *
     * @param  \DateTimeInterface|string  $until
     * @return $this
     */
    public function pauseNowUntil($until)
    {
        return $this->pause(true, $until);
    }

    /**
     * Resume a paused subscription.
     *
     * @return $this
     */
    public function resume()
    {
        $response = Cashier::api('POST', "subscriptions/{$this->paddle_id}/resume")['data'];

        $this->forceFill([
            'status' => $response['status'],
            'ends_at' => null,
            'paused_at' => null,
        ])->save();

        $this->syncSubscriptionItems($response['items']);

        return $this;
    }

    /**
     * Update the underlying Paddle subscription information for the model.
     *
     * @param  array  $options
     * @return array
     */
    public function updatePaddleSubscription(array $options)
    {
        return Cashier::api('PATCH', "subscriptions/{$this->paddle_id}", $options)['data'];
    }

    /**
     * Cancel the subscription at the end of the current billing period.
     *
     * @param  bool  $cancelNow
     * @return $this
     */
    public function cancel(bool $cancelNow = false)
    {
        $response = Cashier::api('POST', "subscriptions/{$this->paddle_id}/cancel", [
            'effective_from' => $cancelNow ? 'immediately' : 'next_billing_period',
        ])['data'];

        $endsAt = $cancelNow ? $response['canceled_at'] : $response['scheduled_change']['effective_at'];

        $this->forceFill([
            'status' => $response['status'],
            'ends_at' => Carbon::parse($endsAt, 'UTC'),
            'trial_ends_at' => $cancelNow ? null : $this->trial_ends_at,
        ])->save();

        return $this;
    }

    /**
     * Cancel the subscription immediately.
     *
     * @return $this
     */
    public function cancelNow()
    {
        return $this->cancel(true);
    }

    /**
     * Stop the subscription from beign canceled at the end of the current billing period.
     *
     * @return $this
     */
    public function stopCancelation()
    {
        $response = $this->updatePaddleSubscription(['scheduled_change' => null]);

        $this->forceFill([
            'status' => $response['status'],
            'ends_at' => null,
        ])->save();

        return $this;
    }

    /**
     * Get the next payment for the subscription.
     *
     * @return \Carbon\Carbon|null
     */
    public function nextBilledAt()
    {
        $paddleSubscription = $this->asPaddleSubscription();

        return $paddleSubscription['next_billed_at']
            ? Carbon::parse($paddleSubscription['next_billed_at'], 'UTC')
            : null;
    }

    /**
     * Get the subscription as a Paddle subscription response.
     *
     * @return array
     */
    public function asPaddleSubscription()
    {
        return Cashier::api('GET', "subscriptions/{$this->paddle_id}")['data'];
    }

    /**
     * Dynamically set the proration behavior when invoicing immediately.
     *
     * @param  string  $method
     * @return void
     *
     * @throws \LogicException
     */
    protected function setProrateAndInvoice($method): void
    {
        if ($this->prorationBehavior === 'do_not_bill') {
            throw new LogicException("You cannot combine {$method} and doNotBill.");
        }

        if ($this->prorationBehavior === 'prorated_next_billing_period') {
            $this->prorateImmediately();
        } elseif ($this->prorationBehavior === 'full_next_billing_period') {
            $this->noProrateImmediately();
        }
    }
}
