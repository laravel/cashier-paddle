<?php

namespace Laravel\Paddle;

use Money\Currency;

class Price
{
    /**
     * The price attributes.
     *
     * @var array
     */
    protected $price;

    /**
     * Create a new Price instance.
     *
     * @param  array  $price
     * @return void
     */
    public function __construct(array $price)
    {
        $this->price = $price;
    }

    /**
     * Get the amount.
     *
     * @return string
     */
    public function amount()
    {
        return Cashier::formatAmount($this->rawAmount(), $this->currency());
    }

    /**
     * Get the raw amount.
     *
     * @return string
     */
    public function rawAmount()
    {
        return $this->price['unit_price']['amount'];
    }

    /**
     * Get the interval for the price.
     *
     * @return string|null
     */
    public function interval()
    {
        return $this->price['billing_cycle']['interval'] ?? null;
    }

    /**
     * Get the frequency for the price.
     *
     * @return int|null
     */
    public function frequency()
    {
        return $this->price['billing_cycle']['frequency'] ?? null;
    }

    /**
     * Get the used currency for the price.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->price['unit_price']['currency_code']);
    }

    /**
     * Dynamically get values from the Paddle price.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->price[$key];
    }
}
