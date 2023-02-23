<?php

namespace Laravel\Paddle;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Money\Currency;

class ProductPrice implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The customer's country for the prices.
     *
     * @var string
     */
    protected $customerCountry;

    /**
     * The Paddle product price attributes.
     *
     * @var array
     */
    protected $product;

    /**
     * Create a new ProductPrice instance.
     *
     * @param  string  $customerCountry
     * @param  array  $product
     * @return void
     */
    public function __construct(string $customerCountry, array $product)
    {
        $this->product = $product;
        $this->customerCountry = $customerCountry;
    }

    /**
     * Get the customer's country for the given product price.
     *
     * @return string
     */
    public function customerCountry()
    {
        return $this->customerCountry;
    }

    /**
     * Get the price for the product with a coupon applied.
     *
     * @return \Laravel\Paddle\Price
     */
    public function price()
    {
        return new Price($this->product['price'], $this->currency());
    }

    /**
     * Get the original listed price for the product.
     *
     * @return \Laravel\Paddle\Price
     */
    public function listPrice()
    {
        return new Price($this->product['list_price'], $this->currency());
    }

    /**
     * Get the initial price for the subscription plan with a coupon applied.
     *
     * @return \Laravel\Paddle\Price
     */
    public function initialPrice()
    {
        return $this->price();
    }

    /**
     * Get the initial original listed price for the subscription plan.
     *
     * @return \Laravel\Paddle\Price
     */
    public function initialListPrice()
    {
        return $this->listPrice();
    }

    /**
     * Get the recurring price for the subscription plan with a coupon applied.
     *
     * @return \Laravel\Paddle\Price|null
     */
    public function recurringPrice()
    {
        if (isset($this->product['subscription'])) {
            return new Price($this->product['subscription']['price'], $this->currency());
        }
    }

    /**
     * Get the recurring original listed price for the subscription plan.
     *
     * @return \Laravel\Paddle\Price|null
     */
    public function recurringListPrice()
    {
        if (isset($this->product['subscription'])) {
            return new Price($this->product['subscription']['list_price'], $this->currency());
        }
    }

    /**
     * Get the amount of trial days for the subscription plan.
     *
     * @return int|null
     */
    public function planTrialDays()
    {
        if (isset($this->product['subscription'])) {
            return $this->product['subscription']['trial_days'];
        }
    }

    /**
     * Get the interval for the subscription plan.
     *
     * @return string|null
     */
    public function planInterval()
    {
        if (isset($this->product['subscription'])) {
            return $this->product['subscription']['interval'];
        }
    }

    /**
     * Get the frequency for the subscription plan.
     *
     * @return int|null
     */
    public function planFrequency()
    {
        if (isset($this->product['subscription'])) {
            return $this->product['subscription']['frequency'];
        }
    }

    /**
     * Get the used currency for the product price.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->product['currency']);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->product;
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
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Dynamically get values from the Paddle product price.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->product[$key];
    }
}
