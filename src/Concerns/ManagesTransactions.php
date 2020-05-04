<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Transaction;

trait ManagesTransactions
{
    /**
     * Get the user's transactions.
     *
     * @param  int  $page
     * @return \Illuminate\Support\Collection
     */
    public function transactions($page = 1)
    {
        if (! $this->hasPaddleId()) {
            return collect();
        }

        $result = Cashier::post("/user/{$this->paddleId()}/transactions", array_merge([
            'page' => $page,
        ], $this->paddleOptions()));

        return collect($result['response'])->map(function (array $transaction) {
            return new Transaction($this, $transaction);
        });
    }
}
