<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Exceptions\InvalidTransaction;

class Transaction
{
    /**
     * The Paddle billable instance.
     *
     * @var \Laravel\Paddle\Billable
     */
    protected $billable;

    /**
     * The Paddle transaction attributes.
     *
     * @var array
     */
    protected $transaction;

    /**
     * Create a new Transaction instance.
     *
     * @param  \Laravel\Paddle\Billable  $billable
     * @param  array  $transaction
     * @return void
     */
    public function __construct($billable, array $transaction)
    {
        if ($billable->paddleId() !== $transaction['user']['user_id']) {
            throw InvalidTransaction::invalidOwner($billable);
        }

        $this->billable = $billable;
        $this->transaction = $transaction;
    }

    /**
     * Get the receipt url.
     *
     * @return string
     */
    public function receiptUrl()
    {
        return $this->transaction['receipt_url'];
    }

    /**
     * Dynamically get values from the Paddle transaction.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->transaction[$key];
    }
}
