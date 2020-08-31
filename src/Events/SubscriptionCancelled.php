<?php
namespace Laravel\Paddle\Events;

use Laravel\Paddle\Subscription;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionCancelled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var \Laravel\Paddle\Subscription
     */
    public $subscription;

    /**
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @return void
     */
    public function __construct(Subscription $subscription, array $payload)
    {
        $this->subscription = $subscription;
        $this->payload = $payload;
    }
}
