<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
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
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'trial_ends_at',
        'ends_at',
    ];

    /**
     * Indicates if the plan change should be prorated.
     *
     * @var bool
     */
    protected $prorate = true;

    /**
     * The cached Paddle info for the subscription.
     *
     * @var array
     */
    protected $paddleInfo;

    /**
     * Get the model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        $model = config('cashier.model');

        return $this->belongsTo($model, (new $model)->getForeignKey());
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
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return (is_null($this->ends_at) || $this->onGracePeriod()) &&
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
                });
        })->where('stripe_status', '!=', self::STATUS_PAUSED);

        if (Cashier::$deactivatePastDue) {
            $query->where('stripe_status', '!=', self::STATUS_PAST_DUE);
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
        return ! $this->onTrial() && ! $this->cancelled();
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
     * @param  int  $amount
     * @param  string  $name
     * @return array
     *
     * @throws \Exception
     *
     * @todo Think about return type here.
     */
    public function charge($amount, $name)
    {
        if (strlen($name) > 50) {
            throw new Exception('Charge name has a maximum length of 50 characters.');
        }

        $payload = $this->owner->paddleOptions([
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
     *  Increment the quantity of the subscription, and invoice immediately.
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
        $this->updateInPaddle(array_merge($options, [
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
        // Todo protect against paused and past_due subscriptions.

        $this->updateInPaddle(array_merge($options, [
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
     * @todo The webhook for this returns a "paused_from" timestamp so pausing is delayed it seems.
     */
    public function pause()
    {
        $this->updateInPaddle([
            'pause' => true,
        ]);

        $this->forceFill([
            'paddle_status' => self::STATUS_PAUSED,
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Resume a paused subscription.
     *
     * @return $this
     */
    public function resume()
    {
        $this->updateInPaddle([
            'pause' => false,
        ]);

        $this->forceFill([
            'paddle_status' => self::STATUS_ACTIVE,
            'ends_at' => null,
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Update the subscription.
     *
     * @param  array  $options
     * @return array
     */
    public function updateInPaddle(array $options)
    {
        $payload = $this->owner->paddleOptions(array_merge([
            'subscription_id' => $this->paddle_id,
        ], $options));

        $response = Cashier::post('/subscription/users/update', $payload)['response'];

        $this->paddleInfo = null;

        return $response;
    }

    /**
     * Get the Paddle update url.
     *
     * @return array
     */
    public function updateUrl()
    {
        return $this->paddleInfo()['update_url'];
    }

    /**
     * Cancel the subscription.
     *
     * @return $this
     */
    public function cancel()
    {
        $nextPayment = $this->nextPayment();

        $payload = $this->owner->paddleOptions([
            'subscription_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/users_cancel', $payload);

        $this->paddle_status = self::STATUS_DELETED;

        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = $nextPayment->date();
        }

        $this->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Cancel the subscription immediately.
     *
     * @return $this
     */
    public function cancelNow()
    {
        $payload = $this->owner->paddleOptions([
            'subscription_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/users_cancel', $payload);

        $this->forceFill([
            'stripe_status' => self::STATUS_DELETED,
            'ends_at' => Carbon::now(),
        ])->save();

        $this->paddleInfo = null;

        return $this;
    }

    /**
     * Get the Paddle cancellation url.
     *
     * @return array
     */
    public function cancelUrl()
    {
        return $this->paddleInfo()['cancel_url'];
    }

    /**
     * Indicate that the plan change should not be prorated.
     *
     * @return $this
     */
    public function noProrate()
    {
        $this->prorate = false;

        return $this;
    }

    /**
     * Indicate that the plan change should be prorated.
     *
     * @return $this
     */
    public function prorate()
    {
        $this->prorate = true;

        return $this;
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
            return null;
        }

        $payment = $this->paddleInfo()['next_payment'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get info from Paddle about the subscription.
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
        ], $this->owner->paddleOptions()))['response'][0];
    }

    /**
     * Get the user's transactions.
     *
     * @param  int  $page
     * @return \Illuminate\Support\Collection
     */
    public function transactions($page = 1)
    {
        $result = Cashier::post("/subscription/{$this->paddle_id}/transactions", array_merge([
            'page' => $page,
        ], $this->owner->paddleOptions()));

        return collect($result['response'])->map(function (array $transaction) {
            return new Transaction($this->owner, $transaction);
        });
    }
}
