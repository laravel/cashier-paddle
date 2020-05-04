<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Concerns\ManagesCustomer;
use Laravel\Paddle\Concerns\ManagesSubscriptions;
use Laravel\Paddle\Concerns\ManagesTransactions;
use Laravel\Paddle\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesTransactions;
    use ManagesSubscriptions;
    use PerformsCharges;

    /**
     * Get the Stripe supported currency used by the entity.
     *
     * @return string
     */
    public function preferredCurrency()
    {
        return config('cashier.currency');
    }

    /**
     * Get the default Stripe API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function paddleOptions(array $options = [])
    {
        return Cashier::paddleOptions($options);
    }
}
