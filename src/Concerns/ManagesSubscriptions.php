<?php

namespace Laravel\Paddle\Concerns;

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
     * Get a subscription instance by name.
     *
     * @param  string  $name
     * @return \Laravel\Paddle\Subscription|null
     */
    public function subscription($name = 'default')
    {
        return $this->subscriptions->sortByDesc(function (Subscription $subscription) {
            return $subscription->created_at->getTimestamp();
        })->first(function (Subscription $subscription) use ($name) {
            return $subscription->name === $name;
        });
    }
}
