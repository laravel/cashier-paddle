<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Laravel\Paddle\Concerns\ManagesAmounts;
use Money\Currency;

class Payment implements Arrayable, Jsonable, JsonSerializable
{
    use ManagesAmounts;

    /**
     * The amount of the payment.
     *
     * @var string
     */
    public $amount;

    /**
     * The currency of the payment.
     *
     * @var string
     */
    public $currency;

    /**
     * The payment date.
     *
     * @var string
     */
    public $date;

    /**
     * Create a new Payment instance.
     *
     * @param  string  $amount
     * @param  string  $currency
     * @param  string  $date
     * @return void
     */
    public function __construct($amount, $currency, $date)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->date = $date;
    }

    /**
     * Get the total amount of the payment.
     *
     * @return string
     */
    public function amount()
    {
        return $this->formatDecimalAmount($this->rawAmount());
    }

    /**
     * Get the raw total of the payment.
     *
     * @return string
     */
    public function rawAmount()
    {
        return $this->amount;
    }

    /**
     * Get the used currency for the payment.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->currency);
    }

    /**
     * Get the date of the payment as a Carbon instance.
     *
     * @return \Carbon\Carbon
     */
    public function date()
    {
        return Carbon::createFromFormat('Y-m-d', $this->date, 'UTC')->startOfDay();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'amount' => $this->amount(),
            'currency' => $this->currency,
            'date' => $this->date()->toIso8601String(),
        ];
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
}
