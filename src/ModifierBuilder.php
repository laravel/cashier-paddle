<?php

namespace Laravel\Paddle;

use InvalidArgumentException;

class ModifierBuilder
{
    /**
     * The Subscription model the modifier belongs to.
     *
     * @var \Laravel\Paddle\Subscription
     */
    protected $subscription;

    /**
     * The amount of the modifier.
     *
     * @var int
     */
    protected $amount;

    /**
     * The description of the modifier.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Indicates whether the modifier should recur.
     *
     * @var bool
     */
    protected $recurring = true;

    /**
     * Create a new modifier builder instance.
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @return void
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Specify the amount of the modifier.
     *
     * @param  int  $amount
     * @return $this
     */
    public function amount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Specify the description of the modifier.
     *
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Specify whether the modifier should be applied only one time.
     *
     * @return $this
     */
    public function oneTime()
    {
        $this->recurring = false;

        return $this;
    }

    /**
     * Create the modifier.
     *
     * @return \Laravel\Paddle\Modifier
     *
     * @throws \Exception
     */
    public function create()
    {
        if (is_null($this->amount)) {
            throw new InvalidArgumentException('Amount is a required property.');
        }

        if (strlen($this->description) > 255) {
            throw new InvalidArgumentException('Description has a maximum length of 255 characters.');
        }

        $response = Cashier::post('/subscription/modifiers/create', $this->buildPayload())['response'];

        return $this->subscription->modifiers()->create([
            'paddle_id' => $response['modifier_id'],
            'amount' => $this->amount,
            'description' => $this->description,
            'recurring' => $this->recurring,
        ]);
    }

    /**
     * Build the payload for modifier creation.
     *
     * @return array
     */
    protected function buildPayload()
    {
        return $this->subscription->billable->paddleOptions([
            'subscription_id' => $this->subscription->paddle_id,
            'modifier_amount' => $this->amount,
            'modifier_description' => $this->description,
            'modifier_recurring' => $this->recurring,
        ]);
    }
}
