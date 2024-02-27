<?php

namespace Laravel\Paddle\Exceptions;

use Exception;

class PaddleException extends Exception
{
    /**
     * The error response from Paddle.
     *
     * @var array
     */
    protected array $error = [];

    /**
     * Get the error response from Paddle.
     *
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * Set the error response from Paddle.
     *
     * @param  array  $error
     * @return self
     */
    public function setError(array $error): self
    {
        $this->error = $error;

        return $this;
    }
}
