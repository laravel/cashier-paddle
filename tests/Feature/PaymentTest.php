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
}
