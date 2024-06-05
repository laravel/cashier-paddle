<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Checkout;
use Laravel\Paddle\NonCatalogCheckout;
use Laravel\Paddle\Subscription;

trait PerformsCharges
{
    /**
     * Get a checkout instance for a given list of prices.
     *
     * @param  string|array  $prices
     * @param  int  $quantity
     * @param  bool  $isFromCatalog
     * @return \Laravel\Paddle\Checkout
     */
    public function checkout($prices, int $quantity = 1, bool $isFromCatalog)
    {
        $customer = $this->createAsCustomer();

        return Checkout::customer($customer, is_array($prices) ? $prices : [$prices => $quantity], $isFromCatalog);
    }

    /**
     * Subscribe the customer to a new plan variant.
     *
     * @param  string|array  $prices
     * @param  string  $type
     * @param  bool  $isFromCatalog
     * @return \Laravel\Paddle\Checkout
     */
    public function subscribe($prices, string $type = Subscription::DEFAULT_TYPE, bool $isFromCatalog = false)
    {
        return $this->checkout($prices, 1, $isFromCatalog)->customData(['subscription_type' => $type]);
    }
}
