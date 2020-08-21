<?php

namespace Tests\Feature;

use Laravel\Paddle\Receipt;
use Money\Currency;

class ReceiptsTest extends FeatureTestCase
{
    public function test_it_can_returns_its_amount_and_currency()
    {
        $receipt = new Receipt([
            'amount' => '12.45',
            'tax' => '4.36',
            'earnings' => '10.45',
            'fee' => '2.00',
            'currency' => 'EUR',
        ]);

        $this->assertSame('€12.45', $receipt->amount());
        $this->assertSame('12.45', $receipt->amount);
        $this->assertSame('€4.36', $receipt->tax());
        $this->assertSame('€10.45', $receipt->earnings());
        $this->assertSame('€2.00', $receipt->fee());
        $this->assertInstanceOf(Currency::class, $receipt->currency());
        $this->assertSame('EUR', $receipt->currency()->getCode());
    }
}
