<?php

namespace Tests\Feature;

use Laravel\Paddle\Transaction;
use Money\Currency;
use Tests\Fixtures\User;

class TransactionsTest extends FeatureTestCase
{
    // This doesn't works atm because we don't have a order.
    // public function test_we_can_retrieve_all_transactions_for_billable_customers()
    // {
    //     $transactions = $customer->transactions();
    //
    //     $this->assertCount(1, $transactions);
    //     $this->assertSame('0', $transactions->first()->amount);
    // }

    public function test_it_can_returns_its_amount_and_currency()
    {
        $billable = new User(['paddle_id' => 1]);
        $transaction = new Transaction($billable, [
            'user' => ['user_id' => 1],
            'amount' => '12.45',
            'currency' => 'EUR',
        ]);

        $this->assertSame('€12.45', $transaction->amount());
        $this->assertSame('12.45', $transaction->rawAmount());
        $this->assertInstanceOf(Currency::class, $transaction->currency());
        $this->assertSame('EUR', $transaction->currency()->getCode());
    }

    public function test_it_can_returns_its_subscription()
    {
        $billable = $this->createCustomer();
        $subscription = $billable->subscriptions()->create([
            'name' => 'default',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => 'active',
            'quantity' => 1,
        ]);
        $transaction = new Transaction($billable, [
            'user' => ['user_id' => $billable->paddleId()],
            'is_subscription' => true,
            'subscription' => [
                'subscription_id' => 244,
            ],
        ]);

        $this->assertTrue($subscription->is($transaction->subscription()));
    }
}
