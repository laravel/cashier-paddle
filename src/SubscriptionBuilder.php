<?php

namespace Laravel\Paddle;

class SubscriptionBuilder
{
    /**
     * The quantity of the subscription.
     *
     * @var int
     */
    protected $quantity = 1;

    /**
     * The interval of the subscription.
     *
     * @var string
     */
    protected $interval = Subscription::INTERVAL_MONTH;

    /**
     * Create a new subscription builder instance.
     *
     * @param  \Laravel\Paddle\Billable  $billable
     * @param  int  $amount
     * @param  string  $name
     * @param  string  $price_description
     * @param  string  $type
     * @return void
     */
    public function __construct(
        protected $billable,
        protected int $amount,
        protected string $name,
        protected string $price_description,
        protected string $type = Subscription::DEFAULT_TYPE
    ) {
    }

    /**
     * Specify the quantity of the subscription.
     *
     * @param  int  $quantity
     * @return $this
     */
    public function quantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Use a daily interval for the subscription.
     */
    public function daily()
    {
        $this->interval = Subscription::INTERVAL_DAY;

        return $this;
    }

    /**
     * Use a weekly interval for the subscription.
     *
     * @return $this
     */
    public function weekly()
    {
        $this->interval = Subscription::INTERVAL_WEEK;

        return $this;
    }

    /**
     * Use a monthly interval for the subscription.
     *
     * @return $this
     */
    public function monthly()
    {
        $this->interval = Subscription::INTERVAL_MONTH;

        return $this;
    }

    /**
     * Use a yearly interval for the subscription.
     *
     * @return $this
     */
    public function yearly()
    {
        $this->interval = Subscription::INTERVAL_YEAR;

        return $this;
    }

    /**
     * Return a new checkout instance for the fresh subscription.
     *
     * @param  array  $options
     * @return \Laravel\Paddle\Checkout
     */
    public function checkout(array $options = [])
    {
        return $this->billable->charge(
            $this->amount,
            $this->name,
            $this->price_description,
            array_merge(
                [
                    'quantity' => $this->quantity,
                ],
                $options
            ),
            [
                'name' => $this->interval === Subscription::INTERVAL_DAY
                    ? 'Daily'
                    : ucfirst($this->interval).'ly',
                'billing_cycle' => [
                    'interval' => $this->interval,
                    'frequency' => $options['frequency'] ?? 1,
                ],
            ]
        )->customData(['subscription_type' => $this->type]);
    }
}
