<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Checkout;
use Laravel\Paddle\Subscription;
use LogicException;

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
     * Generate a new pay link.
     *
     * @param  array  $payload
     * @return string
     */
    protected function generatePayLink(array $payload)
    {
        $payload['customer_email'] = $payload['customer_email'] ?? (string) $this->paddleEmail();
        $payload['customer_country'] = $payload['customer_country'] ?? (string) $this->paddleCountry();
        $payload['customer_postcode'] = $payload['customer_postcode'] ?? (string) $this->paddlePostcode();

        // We'll need a way to identify the user in any webhook we're catching so before
        // we make the API request we'll attach the authentication identifier to this
        // payload so we can match it back to a user when handling Paddle webhooks.
        if (! isset($payload['passthrough'])) {
            $payload['passthrough'] = [];
        }

        if (! is_array($payload['passthrough'])) {
            throw new LogicException('The value for "passthrough" always needs to be an array.');
        }

        $payload['passthrough'] = json_encode($payload['passthrough']);

        $payload = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $payload);

        return Cashier::post('/product/generate_pay_link', $payload)['response']['url'];
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
