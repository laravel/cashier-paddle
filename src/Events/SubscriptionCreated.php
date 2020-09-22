<?php

namespace Laravel\Paddle\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Paddle\Subscription;

class SubscriptionCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The billable entity.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

    /**
     * The subscription instance.
     *
     * @var \Laravel\Paddle\Subscription
     */
    public $subscription;

    /**
     * The payload array.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @param  array  $payload
     * @return void
     */
    public function __construct(Model $billable, Subscription $subscription, array $payload)
    {
        $this->billable = $billable;
        $this->subscription = $subscription;
        $this->payload = $payload;
    }
}
