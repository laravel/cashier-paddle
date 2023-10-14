<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Subscription;

trait ManagesSubscriptions
{
    /**
     * Get all of the subscriptions for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function subscriptions()
    {
        return $this->morphMany(Cashier::$subscriptionModel, 'billable')->orderByDesc('created_at');
    }

    /**
     * Get a subscription instance by type.
     *
     * @param  string  $type
     * @return \Laravel\Paddle\Subscription|null
     */
    public function subscription($type = 'default')
    {
        return $this->subscriptions->where('type', $type)->first();
    }

    /**
     * Determine if the Billable model is on trial.
     *
     * @param  string  $type
     * @param  int|null  $plan
     * @return bool
     */
    public function onTrial($type = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->onTrial()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the Billable model's trial has ended.
     *
     * @param  string  $type
     * @param  int|null  $plan
     * @return bool
     */
    public function hasExpiredTrial($type = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->hasExpiredGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->hasExpiredTrial()) {
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
        if (is_null($this->customer)) {
            return false;
        }

        return $this->customer->onGenericTrial();
    }

    /**
     * Determine if the Billable model's "generic" trial at the model level has expired.
     *
     * @return bool
     */
    public function hasExpiredGenericTrial()
    {
        if (is_null($this->customer)) {
            return false;
        }

        return $this->customer->hasExpiredGenericTrial();
    }

    /**
     * Get the ending date of the trial.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Carbon|null
     */
    public function trialEndsAt($type = 'default')
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return $this->customer->trial_ends_at;
        }

        if ($subscription = $this->subscription($type)) {
            return $subscription->trial_ends_at;
        }

        return $this->customer->trial_ends_at;
    }

    /**
     * Determine if the Billable model has a given subscription.
     *
     * @param  string  $type
     * @param  int|null  $plan
     * @return bool
     */
    public function subscribed($type = 'default', $plan = null)
    {
        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return $plan ? $subscription->hasPlan($plan) : true;
    }

    /**
     * Determine if the Billable model is actively subscribed to one of the given plans.
     *
     * @param  int  $plan
     * @param  string  $type
     * @return bool
     */
    public function subscribedToPlan($plan, $type = 'default')
    {
        $subscription = $this->subscription($type);

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
        return ! is_null($this->subscriptions()
            ->where('paddle_plan', $plan)
            ->get()
            ->first(function (Subscription $subscription) {
                return $subscription->valid();
            }));
    }
}
