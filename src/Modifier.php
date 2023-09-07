<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Concerns\ManagesAmounts;
use Money\Currency;

class Modifier
{
    use ManagesAmounts;

    /**
     * The Subscription model the modifier belongs to.
     *
     * @var \Laravel\Paddle\Subscription
     */
    protected $subscription;

    /**
     * The raw modifier array as returned by Paddle.
     *
     * @var array
     */
    protected $modifier;

    /**
     * Create a new modifier instance.
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @param  array  $modifier
     * @return void
     */
    public function __construct(Subscription $subscription, array $modifier)
    {
        $this->subscription = $subscription;
        $this->modifier = $modifier;
    }

    /**
     * Get the modifier's Paddle ID.
     *
     * @return int
     */
    public function id()
    {
        return $this->modifier['modifier_id'];
    }

    /**
     * Get the related subscription.
     *
     * @return \Laravel\Paddle\Subscription
     */
    public function subscription()
    {
        return $this->subscription;
    }

    /**
     * Get the total amount.
     *
     * @return string
     */
    public function amount()
    {
        return $this->formatDecimalAmount($this->rawAmount());
    }

    /**
     * Get the raw total amount.
     *
     * @return float
     */
    public function rawAmount()
    {
        return $this->modifier['amount'];
    }

    /**
     * Get the currency.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->modifier['currency']);
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function description()
    {
        return $this->modifier['description'];
    }

    /**
     * Indicates whether the modifier is recurring.
     *
     * @return bool
     */
    public function recurring()
    {
        return (bool) $this->modifier['is_recurring'];
    }

    /**
     * Deletes itself on Paddle.
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function delete()
    {
        $payload = $this->subscription->billable->paddleOptions([
            'modifier_id' => $this->id(),
        ]);

        return Cashier::post('/subscription/modifiers/delete', $payload);
    }
}
