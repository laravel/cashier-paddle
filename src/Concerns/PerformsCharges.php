<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Checkout;
use Laravel\Paddle\Subscription;
use Laravel\Paddle\Transaction;
use LogicException;

trait PerformsCharges
{
    /**
     * Get a checkout for a given list of prices.
     *
     * @param  string|array  $prices
     * @param  int  $quantity
     * @return \Laravel\Paddle\Checkout
     */
    public function checkout($prices, int $quantity = 1)
    {
        if (! $customer = $this->customer) {
            $customer = $this->createAsCustomer();
        }

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
        return $this->checkout($prices, 1)->customData(['subscription_type' => $type]);
    }

    /**
     * Refund a given transaction and its specific items.
     *
     * @param  \Laravel\Paddle\Transaction|string  $transactionId
     * @param  string  $reason
     * @param  array|string  $items
     * @return array
     */
    public function refund($transactionId, string $reason, $items)
    {
        $transaction = $transactionId instanceof Transaction
            ? $transactionId
            : $this->transactions()->findOrFail($transactionId);

        if ($transaction->status !== 'billed' && $transaction->status !== 'completed') {
            throw new LogicException('Only "billed" or "completed" transactions can be refunded.');
        }

        return Cashier::api('POST', 'adjustments', [
            'action' => 'refund',
            'transaction_id' => $transaction->paddle_id,
            'reason' => $reason,
            'items' => $items,
        ])['data'];
    }

    /**
     * Credit a given transaction and its specific items.
     *
     * @param  \Laravel\Paddle\Transaction|string  $transactionId
     * @param  string  $reason
     * @param  array|string  $items
     * @return array
     */
    public function credit($transactionId, string $reason, $items)
    {
        $transaction = $transactionId instanceof Transaction
            ? $transactionId
            : $this->transactions()->findOrFail($transactionId);

        if ($transaction->status !== 'billed' && $transaction->status !== 'completed') {
            throw new LogicException('Only "billed" or "completed" transactions can be credited.');
        }

        return Cashier::api('POST', 'adjustments', [
            'action' => 'credit',
            'transaction_id' => $transaction->paddle_id,
            'reason' => $reason,
            'items' => $items,
        ])['data'];
    }
}
