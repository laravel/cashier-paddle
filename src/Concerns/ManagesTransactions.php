<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;

trait ManagesTransactions
{
    /**
     * Get all of the transactions for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function transactions()
    {
        return $this->morphMany(Cashier::$transactionModel, 'billable')->orderByDesc('billed_at');
    }
}
