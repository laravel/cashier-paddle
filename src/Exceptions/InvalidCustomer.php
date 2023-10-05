<?php

namespace Laravel\Paddle\Exceptions;

use Exception;

class InvalidCustomer extends Exception
{
    public static function notYetCreated($owner): static
    {
        return new static(class_basename($owner).' is not a Paddle customer yet.');
    }
}
