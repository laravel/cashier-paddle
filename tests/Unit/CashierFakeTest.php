<?php

namespace Tests\Unit;

use Illuminate\Foundation\Events\Dispatchable;
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
            CashierFake::PATH_PAYMENT_REFUND => $expected = ['fake' => 'response'],
        ]);

        $this->assertEquals(
            json_encode($expected),
            Http::get(CashierFake::retrieveEndpoint(CashierFake::PATH_PAYMENT_REFUND))->body()
        );
    }

    public function test_a_user_may_append_additional_events_to_mock()
    {
        Cashier::fake(events: [CapturedTestEvent::class]);

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
