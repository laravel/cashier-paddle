<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Modifier;
use Laravel\Paddle\Subscription;
use Money\Currency;

class ModifiersTest extends FeatureTestCase
{
    public function test_subscriptions_can_return_their_modifiers()
    {
        Cashier::fake([
            'subscription/modifiers' => [
                'success' => true,
                'response' => [[
                    'modifier_id' => 6789,
                    'sucscription_id' => 3423423,
                    'amount' => 15.00,
                    'currency' => 'EUR',
                    'is_recurring' => false,
                    'description' => 'This is a test modifier',
                ]],
            ],
        ]);

        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 3423423,
            'paddle_plan' => 12345,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $modifier = $subscription->modifiers()->first();
        $this->assertEquals($modifier->id(), 6789);
        $this->assertEquals($modifier->subscription()->paddle_id, $subscription->paddle_id);
        $this->assertEquals($modifier->amount(), '€15.00');
        $this->assertEquals($modifier->rawAmount(), 15.00);
        $this->assertInstanceOf(Currency::class, $modifier->currency());
        $this->assertEquals($modifier->currency()->getCode(), 'EUR');
        $this->assertEquals($modifier->description(), 'This is a test modifier');
        $this->assertEquals($modifier->recurring(), false);

        $modifier = $subscription->modifier(6789);
        $this->assertEquals($modifier->id(), 6789);
        $this->assertEquals($modifier->subscription()->paddle_id, $subscription->paddle_id);
        $this->assertEquals($modifier->amount(), '€15.00');
        $this->assertEquals($modifier->rawAmount(), 15.00);
        $this->assertInstanceOf(Currency::class, $modifier->currency());
        $this->assertEquals($modifier->currency()->getCode(), 'EUR');
        $this->assertEquals($modifier->description(), 'This is a test modifier');
        $this->assertEquals($modifier->recurring(), false);
    }

    public function test_subscriptions_can_create_modifiers()
    {
        Cashier::fake()
            ->response('subscription/users', [[
                'last_payment' => [
                    'amount' => 0.00,
                    'currency' => 'EUR',
                    'date' => '',
                ]
            ]])
            ->response('subscription/modifiers/create', [
                'subscription_id' => 3423423,
                'modifier_id' => 6789,
            ]);

        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 3423423,
            'paddle_plan' => 12345,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        /** @var \Laravel\Paddle\Modifier $modifier */
        $modifier = $subscription->newModifier(15.00)
            ->description('Our test description')
            ->oneTime()
            ->create();

        $this->assertEquals($modifier->id(), 6789);
        $this->assertEquals($modifier->subscription()->paddle_id, $subscription->paddle_id);
        $this->assertEquals($modifier->amount(), '€15.00');
        $this->assertEquals($modifier->rawAmount(), 15.00);
        $this->assertInstanceOf(Currency::class, $modifier->currency());
        $this->assertEquals($modifier->currency()->getCode(), 'EUR');
        $this->assertEquals($modifier->description(), 'Our test description');
        $this->assertEquals($modifier->recurring(), false);

        Http::assertSent(function ($request) {
            if ($request->url() === 'https://vendors.paddle.com/api/2.0/subscription/modifiers/create') {
                return $request['subscription_id'] == 3423423 &&
                   $request['modifier_amount'] == 15.00 &&
                   $request['modifier_description'] == 'Our test description' &&
                   $request['modifier_recurring'] == false;
            }

            return true;
        });
    }

    public function test_a_modifier_can_delete_itself()
    {
        Cashier::fake();

        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 3423423,
            'paddle_plan' => 12345,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $modifier = new Modifier($subscription, [
            'modifier_id' => 6789,
            'amount' => 15.00,
            'currency' => 'EUR',
            'is_recurring' => false,
            'description' => 'Description',
        ]);

        $modifier->delete();

        Http::assertSent(function ($request) {
            return $request['modifier_id'] == 6789;
        });
    }
}
