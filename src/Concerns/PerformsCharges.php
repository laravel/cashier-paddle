<?php

namespace Laravel\Paddle\Concerns;

use Exception;
use Laravel\Paddle\Cashier;

trait PerformsCharges
{
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param  int  $amount
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
            'prices' => [
                'EUR:'.$amount,
            ],
        ], $options, $this->paddleOptions()));
    }

    /**
     * Make a "one off" charge on the customer for a given product.
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
     * @param  array  $options
     * @return string
     */
    protected function generatePayLink(array $options)
    {
        return Cashier::makeApiCall('/product/generate_pay_link', $options)['response']['url'];
    }

    /**
     * Request a refund of a given order.
     *
     * @param  int  $orderId
     * @param  int|null  $amount
     * @param  string  $reason
     * @return bool
     */
    public function refund($orderId, $amount = null, $reason = '')
    {
        $options = array_merge([
            'order_id' => $orderId,
            'reason' => $reason,
        ], $this->paddleOptions());

        if ($amount) {
            $options['amount'] = $amount;
        }

        return Cashier::makeApiCall('/payment/refund', $options)['success'];
    }
}
