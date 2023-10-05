<?php

namespace Laravel\Paddle\Exceptions;

use Exception;
use Laravel\Paddle\Customer;

class CustomerAlreadyCreated extends Exception
{
    public static function exists(Customer $customer): static
    {
        return new static(class_basename($customer->billable)." is already a Paddle customer with ID {$customer->paddle_id}.");
    }
}
