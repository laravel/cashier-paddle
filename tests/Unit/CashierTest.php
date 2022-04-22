<?php

namespace Tests\Unit;

use Laravel\Paddle\Cashier;
use Tests\TestCase;

class CashierTest extends TestCase
{
    public function test_it_can_format_an_amount()
    {
        $this->assertSame('$10.00', Cashier::formatAmount(1000));
    }

    public function test_it_can_format_an_amount_without_ending_digits()
    {
        $this->assertSame('$10', Cashier::formatAmount(1000, null, null, ['min_fraction_digits' => 0]));
        $this->assertSame('$10.1', Cashier::formatAmount(1010, null, null, ['min_fraction_digits' => 0]));
    }
}
