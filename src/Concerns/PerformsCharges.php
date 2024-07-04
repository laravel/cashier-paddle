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
     * Creates a transaction for a "one off" charge for the given amount and returns a checkout instance.
     *
     * @param  int  $amount
     * @param  string  $title
     * @param  array  $options
     * @param  array  $priceData
     * @return \Laravel\Paddle\Checkout
     */
    public function charge(int $amount, string $name, array $options = [], array $priceData = [])
    {
        return $this->chargeMany([[
            'price' => array_filter(array_merge([
                'unit_price' => [
                    'amount' => (string) $amount,
                    'currency_code' => $options['currency'] ?? config('cashier.currency'),
                ],
                'product' => array_filter([
                    'name' => $name,
                    'tax_category' => $options['tax_category'] ?? 'standard',
                    'description' => $options['description'] ?? null,
                ]),
            ]), $priceData),
            'quantity' => $options['quantity'] ?? 1,
        ]]);
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

        $transaction = Cashier::api('POST', 'transactions', ['items' => $items])->json();

        return Checkout::transaction($transaction, $customer);
    }

    /**
     * Subscribe the customer to a new plan variant.
     *
     * @param  string  $name
     * @param  int  $amount
     * @param  string  $type
     * @return \Laravel\Paddle\SubscriptionBuilder
     */
    public function newSubscription($name, $amount, string $type = Subscription::DEFAULT_TYPE)
    {
        return new SubscriptionBuilder($this, $name, $amount, $type);
    }
}
