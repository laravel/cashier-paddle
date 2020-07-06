<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Laravel\Paddle\Exceptions\InvalidTransaction;
use Money\Currency;

class Transaction implements Arrayable, Jsonable, JsonSerializable
{
    const STATUS_COMPLETED = 'completed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    const STATUS_DISPUTED = 'disputed';

    /**
     * The Customer model instance.
     *
     * @var \Laravel\Paddle\Customer
     */
    protected $customer;

    /**
     * The Paddle transaction attributes.
     *
     * @var array
     */
    protected $transaction;

    /**
     * Create a new Transaction instance.
     *
     * @param  \Laravel\Paddle\Customer  $customer
     * @param  array  $transaction
     * @return void
     *
     * @throws \Laravel\Paddle\Exceptions\InvalidTransaction
     */
    public function __construct(Customer $customer, array $transaction)
    {
        if ($customer->paddle_id !== $transaction['user']['user_id']) {
            throw InvalidTransaction::invalidCustomer($customer);
        }

        $this->customer = $customer;
        $this->transaction = $transaction;
    }

    /**
     * Get the total amount that was paid.
     *
     * @return string
     */
    public function amount()
    {
        return $this->formatAmount((int) ($this->rawAmount() * 100));
    }

    /**
     * Get the raw total amount that was paid.
     *
     * @return string
     */
    public function rawAmount()
    {
        return $this->transaction['amount'];
    }

    /**
     * Get the used currency for the transaction.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->transaction['currency']);
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->transaction['currency']);
    }

    /**
     * Get the created at Carbon instance.
     *
     * @return \Carbon\Carbon
     */
    public function date()
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->transaction['created_at'], 'UTC');
    }

    /**
     * Get the receipt url.
     *
     * @return string
     */
    public function receipt()
    {
        return $this->transaction['receipt_url'];
    }

    /**
     * Get the related customer.
     *
     * @return \Laravel\Paddle\Customer
     */
    public function customer()
    {
        return $this->customer;
    }

    /**
     * Get the related subscription.
     *
     * @return \Laravel\Paddle\Subscription|null
     */
    public function subscription()
    {
        if ($this->isSubscription()) {
            return $this->customer->billable->subscriptions()
                ->where('paddle_id', $this->transaction['subscription']['subscription_id'])
                ->first();
        }
    }

    /**
     * Determine if the transaction was for a subscription.
     *
     * @return bool
     */
    public function isSubscription()
    {
        return $this->transaction['is_subscription'];
    }

    /**
     * Determine if the transaction was for a subscription.
     *
     * @return bool
     */
    public function isOneOff()
    {
        return $this->transaction['is_one_off'];
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->transaction;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
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
