<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Laravel\Paddle\Customer;
use Laravel\Paddle\Exceptions\InvalidTransaction;
use Laravel\Paddle\Transaction;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function test_it_throws_an_exception_for_an_invalid_owner()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = ['user' => ['user_id' => 2]];

        $this->expectException(InvalidTransaction::class);

        new Transaction($customer, $transaction);
    }

    public function test_it_can_returns_its_customer()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, [
            'user' => ['user_id' => 1],
        ]);

        $this->assertSame($customer, $transaction->customer());
    }

    public function test_it_can_return_its_currency()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, [
            'user' => ['user_id' => 1],
            'currency' => 'EUR',
        ]);
        $currency = $transaction->currency();

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame('EUR', $currency->getCode());
    }

    public function test_it_can_returns_its_receipt_url()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, [
            'user' => ['user_id' => 1],
            'receipt_url' => 'https://example.com/receipt.pdf',
        ]);

        $this->assertSame('https://example.com/receipt.pdf', $transaction->receipt());
    }

    public function test_it_can_returns_its_created_at_timestamp()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, [
            'user' => ['user_id' => 1],
            'created_at' => '2020-05-07 10:53:17',
        ]);

        $this->assertInstanceOf(Carbon::class, $transaction->date());
        $this->assertSame('2020-05-07 10:53:17', $transaction->date()->format('Y-m-d H:i:s'));
    }

    public function test_it_can_determine_if_it_is_a_subscription_transaction()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, [
            'user' => ['user_id' => 1],
            'is_subscription' => true,
            'is_one_off' => false,
        ]);

        $this->assertTrue($transaction->isSubscription());
        $this->assertFalse($transaction->isOneOff());
    }

    public function test_it_can_determine_if_it_is_a_one_off_transaction()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, [
            'user' => ['user_id' => 1],
            'is_subscription' => false,
            'is_one_off' => true,
        ]);

        $this->assertFalse($transaction->isSubscription());
        $this->assertTrue($transaction->isOneOff());
    }

    public function test_it_implements_arrayable_and_jsonable()
    {
        $customer = new Customer(['paddle_id' => 1]);
        $transaction = new Transaction($customer, $data =[
            'user' => ['user_id' => 1],
            'is_subscription' => false,
            'is_one_off' => true,
        ]);

        $this->assertSame($data, $transaction->toArray());
        $this->assertSame($data, $transaction->jsonSerialize());
        $this->assertSame(json_encode($data), $transaction->toJson());
    }
}
