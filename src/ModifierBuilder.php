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
     * @var float
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
     * @param  float  $amount
     * @return void
     */
    public function __construct(Subscription $subscription, $amount)
    {
        $this->subscription = $subscription;
        $this->amount = $amount;
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
     * @throws \InvalidArgumentException
     */
    public function create()
    {
        if (strlen($this->description) > 255) {
            throw new InvalidArgumentException('Description has a maximum length of 255 characters.');
        }

        $response = Cashier::post('/subscription/modifiers/create', $this->buildPayload())['response'];

        return new Modifier($this->subscription, [
            'modifier_id' => $response['modifier_id'],
            'amount' => $this->amount,
            'currency' => $this->subscription->lastPayment()->currency,
            'is_recurring' => $this->recurring,
            'description' => $this->description,
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
