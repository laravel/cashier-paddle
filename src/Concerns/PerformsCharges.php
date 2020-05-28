<?php

namespace Laravel\Paddle\Concerns;

use Exception;
use Laravel\Paddle\Cashier;

trait PerformsCharges
{
    /**
     * Generate a pay link for a "one off" charge on the customer for the given amount.
     *
     * @param  float|array  $amount
     * @param  string  $title
     * @param  array  $options
     * @return string
     *
     * @throws \Exception
     */
    public function charge($amount, $title, array $options = [])
    {
        if (strlen($title) > 100) {
            throw new Exception('Charge title has a maximum length of 100 characters.');
        }

        return $this->generatePayLink(array_merge([
            'title' => $title,
            'webhook_url' => Cashier::webhookUrl(),
            'prices' => is_array($amount) ? $amount : [config('cashier.currency').':'.$amount],
        ], $options, $this->paddleOptions()));
    }

    /**
     * Generate a pay link for a product.
     *
     * @param  int  $productId
     * @param  array  $options
     * @return string
     */
    public function chargeProduct($productId, array $options = [])
    {
        return $this->generatePayLink(array_merge([
            'product_id' => $productId,
        ], $options, $this->paddleOptions()));
    }

    /**
     * Generate a new pay link.
     *
     * @param  array  $payload
     * @return string
     */
    protected function generatePayLink(array $payload)
    {
        // Because Paddle associates users based on email address, we need to re-use
        // the email address that's being set in Paddle on new payment link generations
        // to make sure Paddle associates them with the same user within Paddle.
        $payload['customer_email'] = $this->paddle_email ?: (string) $this->paddleEmail();

        $payload['customer_country'] = (string) $this->paddleCountry();
        $payload['customer_postcode'] = (string) $this->paddlePostcode();

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

        return Cashier::post('/payment/refund', $payload)['refund_request_id'];
    }
}
