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
     * @param  int|null  $price
     * @return bool
     */
    public function onTrial($type = 'default', $price = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->onTrial()) {
            return false;
        }

        return $price ? $subscription->hasPrice($price) : true;
    }

    /**
     * Determine if the Billable model's trial has ended.
     *
     * @param  string  $type
     * @param  int|null  $price
     * @return bool
     */
    public function hasExpiredTrial($type = 'default', $price = null)
    {
        if (func_num_args() === 0 && $this->hasExpiredGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (! $subscription || ! $subscription->hasExpiredTrial()) {
            return false;
        }

        return $price ? $subscription->hasPrice($price) : true;
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
        if (is_null($this->customer)) {
            return null;
        }

        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return $this->customer->trial_ends_at;
        }

        if ($subscription = $this->subscription($type)) {
            return $subscription->trial_ends_at;
        }

        return $this->customer->trial_ends_at;
    }

    /**
     * Determine if the customer has a given subscription.
     *
     * @param  string  $name
     * @param  string|null  $price
     * @return bool
     */
    public function subscribed($name = 'default', $price = null)
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return $price ? $subscription->hasPrice($price) : true;
    }

    /**
     * Determine if the customer is actively subscribed to one of the given products.
     *
     * @param  string|string[]  $products
     * @param  string  $name
     * @return bool
     */
    public function subscribedToProduct($products, $name = 'default')
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        foreach ((array) $products as $product) {
            if ($subscription->hasProduct($product)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the customer is actively subscribed to one of the given prices.
     *
     * @param  string|string[]  $prices
     * @param  string  $name
     * @return bool
     */
    public function subscribedToPrice($prices, $name = 'default')
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        foreach ((array) $prices as $price) {
            if ($subscription->hasPrice($price)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the customer has a valid subscription on the given product.
     *
     * @param  string  $product
     * @return bool
     */
    public function onProduct($product)
    {
        return ! is_null($this->subscriptions->first(function (Subscription $subscription) use ($product) {
            return $subscription->valid() && $subscription->hasProduct($product);
        }));
    }

    /**
     * Determine if the customer has a valid subscription on the given price.
     *
     * @param  string  $price
     * @return bool
     */
    public function onPrice($price)
    {
        return ! is_null($this->subscriptions->first(function (Subscription $subscription) use ($price) {
            return $subscription->valid() && $subscription->hasPrice($price);
        }));
    }
}
