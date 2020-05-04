<?php

namespace Laravel\Paddle\Exceptions;

use Exception;

class InvalidTransaction extends Exception
{
    /**
     * Create a new InvalidInvoice instance.
     *
     * @param  array  $transaction
     * @param  \Laravel\Paddle\Billable  $billable
     * @return static
     */
    public static function invalidOwner($billable)
    {
        return new static("The transaction does not belong to this customer `{$billable->paddleId()}`.");
    }
}
