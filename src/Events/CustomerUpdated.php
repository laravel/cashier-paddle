<?php

namespace Laravel\Paddle\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Paddle\Customer;

class CustomerUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * The billable entity.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

    /**
     * The customer instance.
     *
     * @var \Laravel\Paddle\Customer
     */
    public $customer;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Laravel\Paddle\Customer  $customer
     * @param  array  $payload
     * @return void
     */
    public function __construct(Model $billable, Customer $customer, array $payload)
    {
        $this->billable = $billable;
        $this->customer = $customer;
        $this->payload = $payload;
    }
}
