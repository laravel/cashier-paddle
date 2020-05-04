<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Exceptions\InvalidCustomer;

trait ManagesCustomer
{
    /**
     * Retrieve the Paddle user ID.
     *
     * @return int|null
     */
    public function paddleId()
    {
        return $this->paddle_id ? (int) $this->paddle_id : null;
    }

    /**
     * Determine if the entity has a Paddle user ID.
     *
     * @return bool
     */
    public function hasPaddleId()
    {
        return ! is_null($this->paddleId());
    }

    /**
     * Determine if the entity has a Paddle user ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \Laravel\Paddle\Exceptions\InvalidCustomer
     */
    protected function assertCustomerExists()
    {
        if (! $this->hasPaddleId()) {
            throw InvalidCustomer::notYetCreated($this);
        }
    }


    /**
     * Get the customer's email address to associate with Paddle.
     *
     * @return string|null
     */
    public function paddleEmail()
    {
        return $this->email;
    }

    /**
     * Get the customer's country to associate with Paddle.
     *
     * This needs to be a 2 letter code. See the link below for supported countries.
     *
     * @return string|null
     * @link https://developer.paddle.com/reference/platform-parameters/supported-countries
     */
    public function paddleCountry()
    {
        //
    }

    /**
     * Get the customer's postcode to associate with Paddle.
     *
     * See the link below for countries which require this.
     *
     * @return string|null
     * @link https://developer.paddle.com/reference/platform-parameters/supported-countries#countries-requiring-postcode
     */
    public function paddlePostcode()
    {
        //
    }
}
