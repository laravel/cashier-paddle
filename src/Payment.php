<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use Money\Currency;

class Payment
{
    /**
     *  The amount of the payment.
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
        return $this->formatAmount((int) ($this->rawAmount() * 100));
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
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->currency);
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
}
