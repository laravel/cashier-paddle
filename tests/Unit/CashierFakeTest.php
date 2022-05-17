<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\CashierFake;
use Tests\TestCase;

class CashierFakeTest extends TestCase
{
    public function test_a_user_may_overwrite_its_api_responses()
    {
        Cashier::fake([
            'payment/refund' => $expected = ['success' => true, 'response' => ['faked' => 'response']],
        ]);

        $this->assertEquals(
            $expected,
            Http::get(CashierFake::getFormattedVendorUrl('payment/refund'))->json()
        );
    }

    public function test_a_user_may_append_additional_events_to_mock()
    {
        Cashier::fake([], [CapturedTestEvent::class]);

        event(new CapturedTestEvent);
        event(new UncapturedTestEvent);

        Event::assertDispatched(CapturedTestEvent::class);
        Event::assertNotDispatched(UncapturedTestEvent::class);
    }
}

class CapturedTestEvent
{
}

class UncapturedTestEvent
{
}
