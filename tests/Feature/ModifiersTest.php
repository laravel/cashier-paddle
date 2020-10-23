<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Subscription;

class ModifiersTest extends FeatureTestCase
{
    public function test_subscriptions_can_create_modifiers()
    {
        Http::fake([
            'https://vendors.paddle.com/api/2.0/subscription/modifiers/create' => Http::response([
                'success' => true,
                'response' => [
                    'subscription_id' => $_SERVER['PADDLE_TEST_SUBSCRIPTION'],
                    'modifier_id' => 6789,
                ]
            ])
        ]);

        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => $_SERVER['PADDLE_TEST_SUBSCRIPTION'],
            'paddle_plan' => 12345,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $modifier = $subscription->newModifier()
            ->amount(15.00)
            ->description('Our test description')
            ->recurring(false)
            ->create();

        Http::assertSent(function ($request) {
            return $request['subscription_id'] == $_SERVER['PADDLE_TEST_SUBSCRIPTION'] &&
                   $request['modifier_amount'] == 15.00 &&
                   $request['modifier_description'] == 'Our test description' &&
                   $request['modifier_recurring'] == false;
        });

        $this->assertEquals($modifier->id, $subscription->modifier($modifier->id)->id);
        $this->assertDatabaseHas('modifiers', [
            'subscription_id' => $subscription->id,
            'paddle_id' => 6789,
            'amount' => 15.00,
            'description' => 'Our test description',
            'recurring' => false,
        ]);
    }
}
