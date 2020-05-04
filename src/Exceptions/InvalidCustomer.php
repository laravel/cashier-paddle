<?php

namespace Laravel\Paddle\Exceptions;

use Exception;

class InvalidCustomer extends Exception
{
    /**
     * Create a new InvalidCustomer instance.
     *
     * @param  \Laravel\Paddle\Billable  $billable
     * @return static
     */
    public static function notYetCreated($billable)
    {
        return new static(class_basename($billable).' is not a Paddle customer yet.');
    }
}
