<?php

namespace Laravel\Paddle;

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
        'created_at',
        'updated_at',
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

        $this->fill([
            'quantity' => $quantity,
        ])->save();

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

        $this->fill([
            'paddle_plan' => $plan,
        ])->save();

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

        $this->fill([
            'paddle_status' => self::STATUS_PAUSED,
        ])->save();

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

        $this->fill([
            'paddle_status' => self::STATUS_ACTIVE,
        ])->save();

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

        return Cashier::post('/subscription/users/update', $payload)['response'];
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
        $payload = $this->owner->paddleOptions([
            'subscription_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/users_cancel', $payload);

        $this->fill([
            'paddle_status' => self::STATUS_DELETED,
        ])->save();

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
     * @return \Laravel\Paddle\Payment
     */
    public function nextPayment()
    {
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
