<?php

namespace Laravel\Paddle;

class Modifier
{
    /**
     * The Paddle identifier of the modifier
     *
     * @var string
     */
    protected $id;

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
     * The currency of the modifier.
     *
     * @var int
     */
    protected $currency;

    /**
     * The description of the modifier.
     *
     * @var string
     */
    protected $description;

    /**
     * Indicates whether the modifier should recur.
     *
     * @var bool
     */
    protected $recurring;

    public function __construct($id, $subscription, $amount, $currency, $description, $recurring)
    {
        $this->id = $id;
        $this->subscription = $subscription;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->description = $description;
        $this->recurring = $recurring;
    }

    public function id()
    {
        return $this->id;
    }

    public function subscription()
    {
        return $this->subscription;
    }

    public function amount()
    {
        return $this->amount;
    }

    public function currency()
    {
        return $this->currency;
    }

    public function description()
    {
        return $this->description;
    }

    public function recurring()
    {
        return $this->recurring;
    }

    /**
     * Deletes itself on Paddle.
     *
     * @return bool|null
     */
    public function delete()
    {
        $payload = $this->subscription->billable->paddleOptions([
            'modifier_id' => $this->id(),
        ]);

        return Cashier::post('/subscription/modifiers/delete', $payload);
    }
}
