<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Concerns\ManagesCustomer;
use Laravel\Paddle\Concerns\ManagesSubscriptions;
use Laravel\Paddle\Concerns\ManagesTransactions;
use Laravel\Paddle\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesSubscriptions;
    use ManagesTransactions;
    use PerformsCharges;

    /**
     * Get the default Paddle API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function paddleOptions(array $options = [])
    {
        return Cashier::paddleOptions($options);
    }
}
