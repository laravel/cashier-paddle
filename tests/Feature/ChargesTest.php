<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Transaction;

class ChargesTest extends FeatureTestCase
{
    public function test_payments_can_be_refunded()
    {
        Cashier::fake([
            'adjustments' => [
                'data' => [
                    'id' => 12345,
                ],
            ],
        ]);

        $billable = $this->createBillable();
        $transaction = new Transaction([
            'id' => 12345,
            'paddle_id' => 'txn_123456789',
            'billable_id' => $billable->id,
            'billable_type' => get_class($billable),
            'status' => 'completed',
        ]);

        $response = $billable->refund($transaction, 'Incorrect order', [
            'itemid_' => 'txnitm_123456789',
            'type' => 'full',
        ]);

        $this->assertSame(12345, $response['id']);
    }
}
