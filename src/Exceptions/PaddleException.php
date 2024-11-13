<?php

namespace Laravel\Paddle\Exceptions;

use Exception;

class PaddleException extends Exception
{
    /**
     * The error response from Paddle.
     */
    protected array $error = [];

    /**
     * Get the error response from Paddle.
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * Set the error response from Paddle.
     */
    public function setError(array $error): self
    {
        $this->error = $error;

        return $this;
    }
}
