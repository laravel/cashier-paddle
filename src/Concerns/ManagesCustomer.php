<?php

namespace Laravel\Paddle\Concerns;

trait ManagesCustomer
{
    /**
     * Get the customer's email address to associate with Paddle.
     *
     * @return string|null
     */
    public function paddleEmail()
    {
        return $this->email;
    }
}
