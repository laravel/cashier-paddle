<?php

namespace Tests\Unit;

use Laravel\Paddle\Exceptions\InvalidTransaction;
use Laravel\Paddle\Transaction;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\User;

class TransactionTest extends TestCase
{
    public function test_it_returns_an_empty_collection_if_the_user_is_not_a_customer_yet()
    {
        $billable = new User();

        $transactions = $billable->transactions();

        $this->assertCount(0, $transactions);
    }

    public function test_it_throws_an_exception_for_an_invalid_owner()
    {
        $billable = new User(['paddle_id' => 1]);
        $transaction = ['user' => ['user_id' => 2]];

        $this->expectException(InvalidTransaction::class);

        new Transaction($billable, $transaction);
    }

    public function test_it_can_returns_its_receipt_url()
    {
        $billable = new User(['paddle_id' => 1]);
        $transaction = new Transaction($billable, [
            'user' => ['user_id' => 1],
            'receipt_url' => 'https://example.com/receipt.pdf',
        ]);

        $this->assertSame('https://example.com/receipt.pdf', $transaction->receiptUrl());
    }
}
