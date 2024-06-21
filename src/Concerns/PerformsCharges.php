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
     * Get a checkout instance for a given list of prices.
     *
     * @param  string|array  $prices
     * @param  int  $quantity
     * @return \Laravel\Paddle\Checkout
     */
    public function checkoutFromTransaction($params)
    {
        $customer = $this->createAsCustomer();

        return Checkout::fromTransaction($params, $customer);
    }

    /**
     * Subscribe the customer to a new plan variant.
     *
     * @param  string|array  $prices
     * @param  string  $type
     * @return \Laravel\Paddle\Checkout
     */
    public function subscribeFromTransaction($params, string $type = Subscription::DEFAULT_TYPE)
    {
        return $this->checkoutFromTransaction($params)->customData(['subscription_type' => $type]);
    }
}
