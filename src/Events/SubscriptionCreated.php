<?php
namespace Laravel\Paddle\Events;

use Laravel\Paddle\Subscription;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

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
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @return void
     */
    public function __construct(Model $billable, Subscription $subscription, array $payload)
    {
        $this->billable = $billable;
        $this->subscription = $subscription;
        $this->payload = $payload;
    }
}
