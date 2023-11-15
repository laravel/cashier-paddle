<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Customer;
use Laravel\Paddle\Exceptions\CustomerAlreadyCreated;
use Laravel\Paddle\Exceptions\InvalidCustomer;

trait ManagesCustomer
{
    /**
     * Determine if the customer has a Paddle customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \Laravel\Paddle\Exceptions\InvalidCustomer
     */
    protected function assertCustomerExists()
    {
        if (is_null($this->customer)) {
            throw InvalidCustomer::notYetCreated($this);
        }
    }

    /**
     * Create a Paddle customer for the given model.
     *
     * @throws \Laravel\Paddle\Exceptions\CustomerAlreadyCreated
     */
    public function createAsCustomer(array $options = []): Customer
    {
        if ($customer = $this->customer) {
            throw CustomerAlreadyCreated::exists($customer);
        }

        if (! array_key_exists('name', $options) && $name = $this->paddleName()) {
            $options['name'] = $name;
        }

        if (! array_key_exists('email', $options) && $email = $this->paddleEmail()) {
            $options['email'] = $email;
        }

        $response = Cashier::api('POST', 'customers', $options)['data'];

        $customer = $this->customer()->make();
        $customer->paddle_id = $response['id'];
        $customer->name = $response['name'];
        $customer->email = $response['email'];
        $customer->trial_ends_at = $options['trial_ends_at'] ?? null;
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
