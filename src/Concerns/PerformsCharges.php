<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Checkout;
use Laravel\Paddle\Subscription;
use Laravel\Paddle\SubscriptionBuilder;

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
     * Subscribe the customer to a new product.
     *
     * @param  int  $amount
     * @param  string  $name
     * @param  string  $type
     * @return \Laravel\Paddle\SubscriptionBuilder
     */
    public function newSubscription(int $amount, string $name, string $type = Subscription::DEFAULT_TYPE)
    {
        return new SubscriptionBuilder($this, $amount, $name, $type);
    }

    /**
     * Creates a transaction for a "one off" charge for the given amount and returns a checkout instance.
     *
     * @param  int  $amount
     * @param  string  $name
     * @param  array  $options
     * @return \Laravel\Paddle\Checkout
     */
    public function charge(int $amount, string $name, array $options = [])
    {
        return $this->chargeMany([array_replace_recursive([
            'price' => [
                'description' => "$name Custom Price",
                'unit_price' => [
                    'amount' => (string) $amount,
                    'currency_code' => config('cashier.currency'),
                ],
                'product' => [
                    'name' => $name,
                    'tax_category' => 'standard',
                ],
            ],
            'quantity' => 1,
        ], $options)]);
    }

    /**
     * Creates a transaction for a "one off" charge for the given items and returns a checkout instance.
     *
     * @param  array  $items
     * @return \Laravel\Paddle\Checkout
     */
    public function chargeMany(array $items)
    {
        $customer = $this->createAsCustomer();

        $transaction = Cashier::api('POST', 'transactions', ['items' => $items])->json()['data'];

        return Checkout::transaction($transaction, $customer);
    }
}
