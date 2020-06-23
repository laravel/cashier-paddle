<?php

namespace Laravel\Paddle\Concerns;

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
     * Get all of the subscriptions for the Billable model.
     *
     * @return \Laravel\Paddle\Subscription[]|\Illuminate\Database\Eloquent\Collection
     */
    public function subscriptions()
    {
        if (is_null($this->customer)) {
            return collect();
        }

        return $this->customer->subscriptions;
    }

    /**
     * Get a subscription instance by name.
     *
     * @param  string  $name
     * @return \Laravel\Paddle\Subscription|null
     */
    public function subscription($name = 'default')
    {
        return optional($this->customer)->subscription($name);
    }

    /**
     * Determine if the Billable model is on trial.
     *
     * @param  string  $name
     * @param  int|null  $plan
     * @return bool
     */
    public function onTrial($name = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->onTrial()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the Billable model is on a "generic" trial at the model level.
     *
     * @return bool
     */
    public function onGenericTrial()
    {
        return optional($this->customer)->onGenericTrial();
    }

    /**
     * Determine if the Billable model has a given subscription.
     *
     * @param  string  $name
     * @param  int|null  $plan
     * @return bool
     */
    public function subscribed($name = 'default', $plan = null)
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the Billable model is actively subscribed to one of the given plans.
     *
     * @param  int  $plan
     * @param  string  $name
     * @return bool
     */
    public function subscribedToPlan($plan, $name = 'default')
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return $subscription->hasPlan($plan);
    }

    /**
     * Determine if the entity has a valid subscription on the given plan.
     *
     * @param  int  $plan
     * @return bool
     */
    public function onPlan($plan)
    {
        return $this->customer->onPlan($plan);
    }
}
