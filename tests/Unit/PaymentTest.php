<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Laravel\Paddle\Payment;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function test_it_can_returns_its_created_at_timestamp()
    {
        $transaction = new Payment('12.45', 'EUR', '2020-05-07');

        $this->assertInstanceOf(Carbon::class, $transaction->date());
        $this->assertSame('2020-05-07 00:00:00', $transaction->date()->format('Y-m-d H:i:s'));
    }
}
