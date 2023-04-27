<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Concerns\ManagesAmounts;
use Money\Currency;

class Price
{
    use ManagesAmounts;

    /**
     * The price attributes.
     *
     * @var array
     */
    protected $price;

    /**
     * The price's currency.
     *
     * @var \Money\Currency
     */
    protected $currency;

    /**
     * Create a new Price instance.
     *
     * @param  array  $price
     * @param  \Money\Currency  $currency
     * @return void
     */
    public function __construct(array $price, Currency $currency)
    {
        $this->price = $price;
        $this->currency = $currency;
    }

    /**
     * Get the gross amount.
     *
     * @return string
     */
    public function gross()
    {
        return $this->formatDecimalAmount($this->rawGross());
    }

    /**
     * Get the raw gross amount.
     *
     * @return string
     */
    public function rawGross()
    {
        return $this->price['gross'];
    }

    /**
     * Get the net amount.
     *
     * @return string
     */
    public function net()
    {
        return $this->formatDecimalAmount($this->rawNet());
    }

    /**
     * Get the raw net amount.
     *
     * @return string
     */
    public function rawNet()
    {
        return $this->price['net'];
    }

    /**
     * Get the net amount.
     *
     * @return string
     */
    public function tax()
    {
        return $this->formatDecimalAmount($this->rawTax());
    }

    /**
     * Determine if the price has tax.
     *
     * @return bool
     */
    public function hasTax()
    {
        return $this->rawTax() > 0;
    }

    /**
     * Get the raw tax amount.
     *
     * @return string
     */
    public function rawTax()
    {
        return $this->price['tax'];
    }

    /**
     * Get the used currency for the price.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return $this->currency;
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
