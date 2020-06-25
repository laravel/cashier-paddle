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
        if (is_null($this->customer)) {
            return collect();
        }

        $result = Cashier::post("/user/{$this->customer->paddle_id}/transactions", array_merge([
            'page' => $page,
        ], $this->paddleOptions()));

        return collect($result['response'])->map(function (array $transaction) {
            return new Transaction($this->customer, $transaction);
        });
    }
}
