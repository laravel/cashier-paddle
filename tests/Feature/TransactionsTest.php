<?php

namespace Tests\Feature;

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
}
