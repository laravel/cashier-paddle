<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Checkout;
use Laravel\Paddle\Subscription;

trait PerformsCharges
{
    /**
     * Get a checkout for a given list of prices.
     *
     * @param  string|array  $prices
     * @param  int  $quantity
     * @param  array  $custom
     * @return \Laravel\Paddle\Checkout
     */
    public function checkout($prices, int $quantity = 1, array $custom = [])
    {
        if (! $customer = $this->customer) {
            $customer = $this->createAsCustomer();
        }

        return Checkout::customer($customer, is_array($prices) ? $prices : [$prices => $quantity], $custom);
    }

    /**
     * Subscribe the customer to a new plan variant.
     *
     * @param  string|array  $prices
     * @param  string  $type
     * @param  array  $custom
     * @return \Laravel\Paddle\Checkout
     */
    public function subscribe($prices, string $type = Subscription::DEFAULT_TYPE, array $options = [], array $custom = [])
    {
        return $this->checkout($prices, 1, array_merge($custom, [
            'subscription_type' => $type,
        ]));
    }

    /**
     * Refund a given order.
     *
     * @param  int  $orderId
     * @param  float|null  $amount
     * @param  string  $reason
     * @return int
     */
    public function refund($orderId, $amount = null, $reason = '')
    {
        $payload = array_merge([
            'order_id' => $orderId,
            'reason' => $reason,
        ], $this->paddleOptions());

        if ($amount) {
            $payload['amount'] = $amount;
        }

        return Cashier::post('/payment/refund', $payload)['response']['refund_request_id'];
    }
}
