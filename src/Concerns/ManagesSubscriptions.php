<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Subscription;
use Laravel\Paddle\SubscriptionBuilder;

trait ManagesSubscriptions
{
    /**
     * Begin creating a new subscription.
     *
     * @param  string  $name
     * @param  int  $plan
     * @return \Laravel\Paddle\SubscriptionBuilder
     */
    public function newSubscription($name, $plan)
    {
        return new SubscriptionBuilder($this, $name, $plan);
    }

    /**
     * Get all of the subscriptions for the Stripe model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Cancel the subscription.
     *
     * @return bool
     */
    public function cancel()
    {
        $options = array_merge([
            'subscription_id' => $this->paddle_id,
        ], $this->paddleOptions());

        return Cashier::makeApiCall('/subscription/users_cancel', $options)['success'];
    }
}
