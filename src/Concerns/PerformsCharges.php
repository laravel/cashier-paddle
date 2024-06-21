<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Checkout;
use Laravel\Paddle\Subscription;

trait PerformsCharges
{
    /**
     * Get a checkout instance for a given list of prices.
     *
     * @param  string|array  $prices
     * @param  int  $quantity
     * @return \Laravel\Paddle\Checkout
     */
    public function checkout($prices, int $quantity = 1)
    {
        $customer = $this->createAsCustomer();

        return Checkout::customer($customer, is_array($prices) ? $prices : [$prices => $quantity]);
    }

    /**
     * Subscribe the customer to a new plan variant.
     *
     * @param  string|array  $prices
     * @param  string  $type
     * @return \Laravel\Paddle\Checkout
     */
    public function subscribe($prices, string $type = Subscription::DEFAULT_TYPE)
    {
        return $this->checkout($prices)->customData(['subscription_type' => $type]);
    }

    /**
     * Creates a transaction on paddle and returns a checkout instance.
     *
     * @param  array  $transactionParams
     * @return \Laravel\Paddle\Checkout
     */
    public function checkoutFromTransaction(array $transactionParams)
    {
        $customer = $this->createAsCustomer();

        return Checkout::fromTransaction($transactionParams, $customer);
    }

    /**
     * Subscribe the customer based on a custom paddle transaction.
     *
     * @param  array  $transactionParams
     * @return \Laravel\Paddle\Checkout
     */
    public function subscribeFromTransaction(array $transactionParams, string $type = Subscription::DEFAULT_TYPE)
    {
        return $this->checkoutFromTransaction($transactionParams)->customData(['subscription_type' => $type]);
    }
}
