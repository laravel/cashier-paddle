<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\CashierFake;
use Laravel\Paddle\Exceptions\PaddleException;
use Tests\Feature\FeatureTestCase;

class CashierFakeTest extends FeatureTestCase
{
    public function test_a_user_may_overwrite_its_api_responses()
    {
        Cashier::fake([
            $endpoint = 'payment/refund' => $expected = ['success' => true, 'response' => ['faked' => 'response']],
        ]);

        $this->assertEquals(
            $expected,
            Http::get(CashierFake::getFormattedVendorUrl($endpoint))->json()
        );
    }

    public function test_a_user_may_use_the_response_method_to_mock_an_endpoint()
    {
        Cashier::fake()->response(
            $endpoint = 'payment/refund',
            $expected = ['custom' => 'response']
        );

        $this->assertEquals(
            ['success' => true, 'response' => $expected],
            Http::get(CashierFake::getFormattedVendorUrl($endpoint))->json()
        );
    }

    public function test_a_user_may_use_the_error_method_to_error_an_endpoint()
    {
        $this->expectException(PaddleException::class);
        Cashier::fake()->error('payment/refund');

        $this->createBillable()->refund(4321, 12.50, 'Incorrect order');
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
