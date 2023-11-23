<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Transaction;
use Money\Currency;

class TransactionsTest extends FeatureTestCase
{
    public function test_it_can_returns_its_amount_and_currency()
    {
        $transaction = new Transaction([
            'total' => '1245',
            'tax' => '436',
            'currency' => 'EUR',
        ]);

        $this->assertSame('€12.45', $transaction->total());
        $this->assertSame('1245', $transaction->total);
        $this->assertInstanceOf(Currency::class, $transaction->currency());
        $this->assertSame('EUR', $transaction->currency()->getCode());
    }

    public function test_it_can_returns_a_japanese_amount_and_currency()
    {
        $transaction = new Transaction([
            'total' => '1200',
            'currency' => 'JPY',
        ]);

        $this->assertSame('¥1,200', $transaction->total());
        $this->assertSame('1200', $transaction->total);
        $this->assertSame('JPY', $transaction->currency()->getCode());
    }

    public function test_it_can_return_a_korean_amount_and_currency()
    {
        $transaction = new Transaction([
            'total' => '1200',
            'currency' => 'KRW',
        ]);

        $this->assertSame('₩1,200', $transaction->total());
        $this->assertSame('1200', $transaction->total);
        $this->assertSame('KRW', $transaction->currency()->getCode());
    }

    public function test_transactions_can_be_refunded()
    {
        Cashier::fake([
            'transactions/txn_123456789' => [
                'data' => [
                    'details' => [
                        'line_items' => [
                            [
                                'id' => 'txnitm_12345',
                                'price_id' => 'pri_12345',
                            ],
                        ],
                    ],
                ],
            ],
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

        $response = $transaction->refund('Incorrect order');

        $this->assertSame(12345, $response['id']);
    }
}
