<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
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
    const STATUS_DELETED = 'deleted';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'paddle_id' => 'integer',
        'paddle_plan' => 'integer',
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'paused_from' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * The cached Paddle info for the subscription.
     *
     * @var array
     */
    protected $paddleInfo;

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
     * Get all of the receipts for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receipts()
    {
        return $this->hasMany(Cashier::$receiptModel, 'paddle_subscription_id', 'paddle_id')->orderByDesc('created_at');
    }

    /**
     * Determine if the subscription has a specific plan.
     *
     * @param  int  $plan
     * @return bool
     */
    public function hasPlan($plan)
    {
        return $this->paddle_plan == $plan;
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onPausedGracePeriod() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return (is_null($this->ends_at) || $this->onGracePeriod() || $this->onPausedGracePeriod()) &&
            (! Cashier::$deactivatePastDue || $this->paddle_status !== self::STATUS_PAST_DUE) &&
            $this->paddle_status !== self::STATUS_PAUSED;
    }

    /**
     * Filter query by active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->whereNull('ends_at')
                ->orWhere(function ($query) {
                    $query->onGracePeriod();
                })
                ->orWhere(function ($query) {
                    $query->onPausedGracePeriod();
                });
        })->where('paddle_status', '!=', self::STATUS_PAUSED);

        if (Cashier::$deactivatePastDue) {
            $query->where('paddle_status', '!=', self::STATUS_PAST_DUE);
        }
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->paddle_status === self::STATUS_PAST_DUE;
    }

    /**
     * Filter query by past due.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePastDue($query)
    {
        $query->where('paddle_status', self::STATUS_PAST_DUE);
    }

    /**
     * Determine if the subscription is recurring and not on trial.
     *
     * @return bool
     */
    public function recurring()
    {
        return ! $this->onTrial() && ! $this->paused() && ! $this->onPausedGracePeriod() && ! $this->cancelled();
    }

    /**
     * Filter query by recurring.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeRecurring($query)
    {
        $query->notOnTrial()->notCancelled();
    }

    /**
     * Determine if the subscription is paused.
     *
     * @return bool
     */
    public function paused()
    {
        return $this->paddle_status === self::STATUS_PAUSED;
    }

    /**
     * Filter query by paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePaused($query)
    {
        $query->where('paddle_status', self::STATUS_PAUSED);
    }

    /**
     * Filter query by not paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotPaused($query)
    {
        $query->where('paddle_status', '!=', self::STATUS_PAUSED);
    }

    /**
     * Determine if the subscription is within its grace period after being paused.
     *
     * @return bool
     */
    public function onPausedGracePeriod()
    {
        return $this->paused_from && $this->paused_from->isFuture();
    }

    /**
     * Filter query by on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnPausedGracePeriod($query)
    {
        $query->whereNotNull('paused_from')->where('paused_from', '>', Carbon::now());
    }

    /**
     * Filter query by not on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnPausedGracePeriod($query)
    {
        $query->whereNull('paused_from')->orWhere('paused_from', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function cancelled()
    {
        return ! is_null($this->ends_at);
    }

    /**
     * Filter query by cancelled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeCancelled($query)
    {
        $query->whereNotNull('ends_at');
    }

    /**
     * Filter query by not cancelled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotCancelled($query)
    {
        $query->whereNull('ends_at');
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function ended()
    {
        return $this->cancelled() && ! $this->onGracePeriod();
    }

    /**
     * Filter query by ended.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeEnded($query)
    {
        $query->cancelled()->notOnGracePeriod();
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Filter query by on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
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
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', Carbon::now());
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
     * Perform a "one off" charge on top of the subscription for the given amount.
     *
     * @param  float  $amount
     * @param  string  $name
     * @return array
     *
     * @throws \Exception
     */
    public function charge($amount, $name)
    {
        if (strlen($name) > 50) {
            throw new Exception('Charge name has a maximum length of 50 characters.');
        }

        $payload = $this->billable->paddleOptions([
            'amount' => $amount,
            'charge_name' => $name,
        ]);

        $this->paddleInfo = null;

        return Cashier::post("/subscription/{$this->paddle_id}/charge", $payload)['response'];
    }

    /**
     * Increment the quantity of the subscription.
     *
     * @param  int  $count
     * @return $this
     */
    public function incrementQuantity($count = 1)
    {
        $this->updateQuantity($this->quantity + $count);

        return $this;
    }

    /**
     * Increment the quantity of the subscription, and invoice immediately.
     *
     * @param  int  $count
     * @return $this
     */
    public function incrementAndInvoice($count = 1)
    {
        $this->updateQuantity($this->quantity + $count, [
            'bill_immediately' => true,
        ]);

        return $this;
    }

    /**
     * Decrement the quantity of the subscription.
     *
     * @param  int  $count
     * @return $this
     */
    public function decrementQuantity($count = 1)
    {
        return $this->updateQuantity(max(1, $this->quantity - $count));
    }

    /**
     * Update the quantity of the subscription.
     *
     * @param  int  $quantity
     * @param  array  $options
     * @return $this
     */
    public function updateQuantity($quantity, array $options = [])
    {
        $this->guardAgainstUpdates('update quantities');

        if ($quantity < 1) {
            throw new LogicException('Paddle does not allow subscriptions to have a quantity of zero.');
        }

        $this->updatePaddleSubscription(array_merge($options, [
            'quantity' => $quantity,
            'prorate' => $this->prorate,
        ]));

        $this->forceFill([
            'quantity' => $quantity,
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Swap the subscription to a new Paddle plan.
     *
     * @param  int  $plan
     * @param  array  $options
     * @return $this
     */
    public function swap($plan, array $options = [])
    {
        $this->guardAgainstUpdates('swap plans');

        $this->updatePaddleSubscription(array_merge($options, [
            'plan_id' => $plan,
            'prorate' => $this->prorate,
        ]));

        $this->forceFill([
            'paddle_plan' => $plan,
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Swap the subscription to a new Paddle plan, and invoice immediately.
     *
     * @param  int  $plan
     * @param  array  $options
     * @return $this
     */
    public function swapAndInvoice($plan, array $options = [])
    {
        return $this->swap($plan, array_merge($options, [
            'bill_immediately' => true,
        ]));
    }

    /**
     * Pause the subscription.
     *
     * @return $this
     */
    public function pause()
    {
        $this->updatePaddleSubscription([
            'pause' => true,
        ]);

        $info = $this->paddleInfo();

        $this->forceFill([
            'paddle_status' => $info['state'],
            'paused_from' => Carbon::createFromFormat('Y-m-d H:i:s', $info['paused_from'], 'UTC'),
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Resume a paused subscription.
     *
     * @return $this
     */
    public function unpause()
    {
        $this->updatePaddleSubscription([
            'pause' => false,
        ]);

        $this->forceFill([
            'paddle_status' => self::STATUS_ACTIVE,
            'ends_at' => null,
            'paused_from' => null,
        ])->save();

        $this->paddleInfo = null;

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
        $payload = $this->billable->paddleOptions(array_merge([
            'subscription_id' => $this->paddle_id,
        ], $options));

        $response = Cashier::post('/subscription/users/update', $payload)['response'];

        $this->paddleInfo = null;

        return $response;
    }

    /**
     * Get the Paddle update url.
     *
     * @return string
     */
    public function updateUrl()
    {
        return $this->paddleInfo()['update_url'];
    }

    /**
     * Begin creating a new modifier.
     *
     * @param  float  $amount
     * @return \Laravel\Paddle\ModifierBuilder
     */
    public function newModifier($amount)
    {
        return new ModifierBuilder($this, $amount);
    }

    /**
     * Get all of the modifiers for this subscription.
     *
     * @return \Illuminate\Support\Collection
     */
    public function modifiers()
    {
        $result = Cashier::post('/subscription/modifiers', array_merge([
            'subscription_id' => $this->paddle_id,
        ], $this->billable->paddleOptions()));

        return collect($result['response'])->map(function (array $modifier) {
            return new Modifier($this, $modifier);
        });
    }

    /**
     * Get a modifier instance by ID.
     *
     * @param  int  $id
     * @return \Laravel\Paddle\Modifier|null
     */
    public function modifier($id)
    {
        return $this->modifiers()->first(function (Modifier $modifier) use ($id) {
            return $modifier->id() === $id;
        });
    }

    /**
     * Cancel the subscription at the end of the current billing period.
     *
     * @return $this
     */
    public function cancel()
    {
        if ($this->onGracePeriod()) {
            return $this;
        }

        if ($this->onPausedGracePeriod() || $this->paused()) {
            $endsAt = $this->paused_from->isFuture()
                ? $this->paused_from
                : Carbon::now();
        } else {
            $endsAt = $this->onTrial()
                ? $this->trial_ends_at
                : $this->nextPayment()->date();
        }

        return $this->cancelAt($endsAt);
    }

    /**
     * Cancel the subscription immediately.
     *
     * @return $this
     */
    public function cancelNow()
    {
        return $this->cancelAt(Carbon::now());
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * @param  \DateTimeInterface  $endsAt
     * @return $this
     */
    public function cancelAt(DateTimeInterface $endsAt)
    {
        $payload = $this->billable->paddleOptions([
            'subscription_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/users_cancel', $payload);

        $this->forceFill([
            'paddle_status' => self::STATUS_DELETED,
            'ends_at' => $endsAt,
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Get the Paddle cancellation url.
     *
     * @return string
     */
    public function cancelUrl()
    {
        return $this->paddleInfo()['cancel_url'];
    }

    /**
     * Get the last payment for the subscription.
     *
     * @return \Laravel\Paddle\Payment
     */
    public function lastPayment()
    {
        $payment = $this->paddleInfo()['last_payment'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the next payment for the subscription.
     *
     * @return \Laravel\Paddle\Payment|null
     */
    public function nextPayment()
    {
        if (! isset($this->paddleInfo()['next_payment'])) {
            return;
        }

        $payment = $this->paddleInfo()['next_payment'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the email address of the customer associated to this subscription.
     *
     * @return string
     */
    public function paddleEmail()
    {
        return (string) $this->paddleInfo()['user_email'];
    }

    /**
     * Get the payment method type from the subscription.
     *
     * @return string
     */
    public function paymentMethod()
    {
        return (string) ($this->paddleInfo()['payment_information']['payment_method'] ?? '');
    }

    /**
     * Get the card brand from the subscription.
     *
     * @return string
     */
    public function cardBrand()
    {
        return (string) ($this->paddleInfo()['payment_information']['card_type'] ?? '');
    }

    /**
     * Get the last four digits from the subscription if it's a credit card.
     *
     * @return string
     */
    public function cardLastFour()
    {
        return (string) ($this->paddleInfo()['payment_information']['last_four_digits'] ?? '');
    }

    /**
     * Get the card expiration date.
     *
     * @return string
     */
    public function cardExpirationDate()
    {
        return (string) ($this->paddleInfo()['payment_information']['expiry_date'] ?? '');
    }

    /**
     * Get raw information about the subscription from Paddle.
     *
     * @return array
     */
    public function paddleInfo()
    {
        if ($this->paddleInfo) {
            return $this->paddleInfo;
        }

        return $this->paddleInfo = Cashier::post('/subscription/users', array_merge([
            'subscription_id' => $this->paddle_id,
        ], $this->billable->paddleOptions()))['response'][0];
    }

    /**
     * Perform a guard check to prevent change for a specific action.
     *
     * @param  string  $action
     * @return void
     *
     * @throws \LogicException
     */
    public function guardAgainstUpdates($action): void
    {
        if ($this->onTrial()) {
            throw new LogicException("Cannot $action while on trial.");
        }

        if ($this->paused() || $this->onPausedGracePeriod()) {
            throw new LogicException("Cannot $action for paused subscriptions.");
        }

        if ($this->cancelled() || $this->onGracePeriod()) {
            throw new LogicException("Cannot $action for cancelled subscriptions.");
        }

        if ($this->pastDue()) {
            throw new LogicException("Cannot $action for past due subscriptions.");
        }
    }
}
