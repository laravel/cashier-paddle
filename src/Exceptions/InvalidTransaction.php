<?php

namespace Laravel\Paddle\Exceptions;

use Exception;
use Laravel\Paddle\Customer;

class InvalidTransaction extends Exception
{
    /**
     * Create a new InvalidTransaction instance.
     *
     * @param  \Laravel\Paddle\Customer  $customer
     * @return static
     */
    public static function invalidCustomer(Customer $customer)
    {
        return new static("The transaction does not belong to this customer `{$customer->paddle_id}`.");
    }
}
