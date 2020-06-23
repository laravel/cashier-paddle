<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Laravel\Paddle\Concerns\Prorates;
use LogicException;

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
     * Get the customer related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

        $payload = $this->customer->paddleOptions([
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
        if ($this->paused() || $this->pastDue()) {
            throw new LogicException('Cannot update quantities for paused or past due subscriptions.');
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
        if ($this->paused() || $this->pastDue()) {
            throw new LogicException('Cannot swap plans for paused or past due subscriptions.');
        }

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
        $payload = $this->customer->paddleOptions(array_merge([
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

        $payload = $this->customer->paddleOptions([
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
        $payload = $this->customer->paddleOptions([
            'subscription_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/users_cancel', $payload);

        $this->forceFill([
            'paddle_status' => self::STATUS_DELETED,
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
        ], $this->customer->paddleOptions()))['response'][0];
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
        ], $this->customer->paddleOptions()));

        return collect($result['response'])->map(function (array $transaction) {
            return new Transaction($this->customer, $transaction);
        });
    }

    /**
     * Sync the payment information from Paddle with the subscription.
     *
     * @return $this
     */
    public function syncPaymentInformation()
    {
        $info = $this->paddleInfo()['payment_information'];

        if ($info['payment_method'] === 'card') {
            $this->card_brand = $info['card_type'];
            $this->card_last_four = $info['last_four_digits'];
        } elseif ($info['payment_method'] === 'paypal') {
            $this->card_brand = 'paypal';
            $this->card_last_four = '';
        }

        $this->save();

        return $this;
    }
}
