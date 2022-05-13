<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\CashierFake;
use Tests\TestCase;

class CashierFakeTest extends TestCase
{
    public function test_a_user_may_overwrite_its_responses()
    {
        Cashier::fake([
            CashierFake::PATH_PAYMENT_REFUND => $expected = ['fake' => 'response'],
        ]);

        $this->assertEquals(
            json_encode($expected),
            Http::get(CashierFake::retrieveEndpoint(CashierFake::PATH_PAYMENT_REFUND))->body()
        );
    }
}
