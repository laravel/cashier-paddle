<?php

namespace Tests\Feature;

use Laravel\Paddle\Payment;
use Money\Currency;

class PaymentTest extends FeatureTestCase
{
    public function test_it_can_returns_its_amount_and_currency()
    {
        $payment = new Payment('12.45', 'EUR', '2020-05-07');

        $this->assertSame('€12.45', $payment->amount());
        $this->assertSame('12.45', $payment->rawAmount());
        $this->assertInstanceOf(Currency::class, $payment->currency());
        $this->assertSame('EUR', $payment->currency()->getCode());
    }

    public function test_it_can_returns_a_japanese_amount_and_currency()
    {
        $payment = new Payment('1200.0', 'JPY', '2020-05-07');

        $this->assertSame('¥1,200', $payment->amount());
        $this->assertSame('1200.0', $payment->rawAmount());
        $this->assertSame('JPY', $payment->currency()->getCode());
    }

    public function test_it_can_return_a_korean_amount_and_currency()
    {
        $payment = new Payment('1200.0', 'KRW', '2020-05-07');

        $this->assertSame('₩1,200', $payment->amount());
        $this->assertSame('1200.0', $payment->rawAmount());
        $this->assertSame('KRW', $payment->currency()->getCode());
    }

    public function test_it_can_be_serialized_to_an_array()
    {
        $payment = new Payment('12.45', 'EUR', '2020-05-07');

        $this->assertSame([
            'amount' => '€12.45',
            'currency' => 'EUR',
            'date' => '2020-05-07T00:00:00+00:00',
        ], $payment->toArray());
    }
}
