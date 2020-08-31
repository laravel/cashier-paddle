<?php
namespace Laravel\Paddle\Events;

use Laravel\Paddle\Receipt;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionPaymentSucceeded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

    /**
     * @var \Laravel\Paddle\Receipt
     */
    public $receipt;

    /**
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Laravel\Paddle\Receipt  $receipt
     * @return void
     */
    public function __construct(Model $billable, Receipt $receipt, array $payload)
    {
        $this->billable = $billable;
        $this->receipt = $receipt;
        $this->payload = $payload;
    }

    /**
     * Indicates whether it is the customer’s first payment for this subscription.
     *
     * @return bool
     */
    public function isInitialPayment()
    {
        return $this->payload['initial_payment'] === 1;
    }
}
