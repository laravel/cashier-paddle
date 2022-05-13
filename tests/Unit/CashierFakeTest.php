<?php

namespace Tests\Unit;

use Illuminate\Support\Arr;
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
            $expected,
            Http::get(CashierFake::retrieveEndpoint(CashierFake::PATH_PAYMENT_REFUND))->json()
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

    public function test_a_user_may_overwrite_the_standard_card_data()
    {
        Cashier::fake()->card([$key = 'last_four_digits' => $expected = '9876']);

        $response = Http::get(CashierFake::retrieveEndpoint(CashierFake::PATH_SUBSCRIPTION_USERS))->json();

        $this->assertEquals($expected, Arr::get($response, 'response.0.payment_information.'.$key));
    }
}

class CapturedTestEvent
{
}

class UncapturedTestEvent
{
}
