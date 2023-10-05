<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Concerns\ManagesCustomer;
use Laravel\Paddle\Concerns\ManagesReceipts;
use Laravel\Paddle\Concerns\ManagesSubscriptions;
use Laravel\Paddle\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesSubscriptions;
    use ManagesReceipts;
    use PerformsCharges;
}
