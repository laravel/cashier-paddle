<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use LogicException;

trait ManagesCustomer
{
    /**
     * Create a Paddle customer for the given model.
     *
     * @return \Laravel\Paddle\Customer
     */
    public function createAsCustomer(array $options = [])
    {
        if ($customer = $this->customer) {
            return $customer;
        }

        if (! array_key_exists('name', $options) && $name = $this->paddleName()) {
            $options['name'] = $name;
        }

        if (! array_key_exists('email', $options) && $email = $this->paddleEmail()) {
            $options['email'] = $email;
        }

        if (! isset($options['email'])) {
            throw new LogicException('Unable to create Paddle customer without an email.');
        }

        $trialEndsAt = $options['trial_ends_at'] ?? null;

        unset($options['trial_ends_at']);

        // Attempt to find the customer by email address first...
        $response = Cashier::api('GET', 'customers', [
            'status' => 'active,archived',
            'search' => $options['email'],
        ])['data'][0] ?? null;

        // If we can't find the customer by email, we'll create them on Paddle...
        if (is_null($response)) {
            $response = Cashier::api('POST', 'customers', $options)['data'];
        }

        if (Cashier::$customerModel::where('paddle_id', $response['id'])->exists()) {
            throw new LogicException("This Paddle customer ({$response['id']}' already exists in the database.");
        }

        $customer = $this->customer()->make();
        $customer->paddle_id = $response['id'];
        $customer->name = $response['name'];
        $customer->email = $response['email'];
        $customer->trial_ends_at = $trialEndsAt;
        $customer->save();

        $this->refresh();

        return $customer;
    }

    /**
     * Get the customer related to the billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function customer()
    {
        return $this->morphOne(Cashier::$customerModel, 'billable');
    }

    /**
     * Get price previews for a set of price ids for this billable model.
     *
     * @param  array|string  $items
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function previewPrices($items, array $options = [])
    {
        if ($customer = $this->customer) {
            $options['customer_id'] = $customer->paddle_id;
        }

        return Cashier::previewPrices($items, $options);
    }

    /**
     * Get the billable model's name to associate with Paddle.
     *
     * @return string|null
     */
    public function paddleName()
    {
        return $this->name;
    }

    /**
     * Get the billable model's email address to associate with Paddle.
     *
     * @return string|null
     */
    public function paddleEmail()
    {
        return $this->email;
    }
}
