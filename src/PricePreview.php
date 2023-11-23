<?php

namespace Laravel\Paddle;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Money\Currency;

class PricePreview implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The price preview attributes.
     *
     * @var array
     */
    protected $item;

    /**
     * Create a new PricePreview instance.
     *
     * @param  array  $item
     * @return void
     */
    public function __construct(array $item)
    {
        $this->item = $item;
    }

    /**
     * Get the price object for the preview.
     *
     * @return \Laravel\Paddle\Price
     */
    public function price()
    {
        return new Price($this->item['price']);
    }

    /**
     * Get the total amount.
     *
     * @return string
     */
    public function total()
    {
        return $this->item['formatted_totals']['total'];
    }

    /**
     * Get the raw total amount.
     *
     * @return string
     */
    public function rawTotal()
    {
        return $this->item['totals']['total'];
    }

    /**
     * Get the subtotal amount.
     *
     * @return string
     */
    public function subtotal()
    {
        return $this->item['formatted_totals']['subtotal'];
    }

    /**
     * Get the raw subtotal amount.
     *
     * @return string
     */
    public function rawSubtotal()
    {
        return $this->item['totals']['subtotal'];
    }

    /**
     * Get the tax amount.
     *
     * @return string
     */
    public function tax()
    {
        return $this->item['formatted_totals']['tax'];
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
        return $this->item['totals']['tax'];
    }

    /**
     * Get the used currency for the price preview.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return $this->price()->currency();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->item;
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
     * Dynamically get values from the price preview.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->item[$key];
    }
}
