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
     * @param  array  $options
     * @return \Laravel\Paddle\Checkout
     */
    public function checkout($prices, int $quantity = 1, array $options = [])
    {
        $customer = $this->createAsCustomer();

        return Checkout::customer($customer, is_array($prices) ? $prices : [$prices => $quantity], $options);
    }

    /**
     * Subscribe the customer to a new plan variant.
     *
     * @param  string|array  $prices
     * @param  string  $type
     * @param  array  $options
     * @return \Laravel\Paddle\Checkout
     */
    public function subscribe($prices, string $type = Subscription::DEFAULT_TYPE, array $options = [])
    {
        data_set($options, 'is_subscription', true, overwrite: false);
        return $this->checkout($prices, 1, $options)->customData(['subscription_type' => $type]);
    }
}
