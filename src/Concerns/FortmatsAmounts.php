<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;

trait FortmatsAmounts
{
    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->currency());
    }
}
