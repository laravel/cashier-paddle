<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Laravel\Paddle\Subscription;
use LogicException;

class SubscriptionsTest extends FeatureTestCase
{
    public function test_cannot_swap_while_on_trial()
    {
        $subscription = new Subscription(['trial_ends_at' => now()->addDay()]);

        $this->expectExceptionObject(new LogicException('Cannot swap plans while on trial.'));

        $subscription->swap(123);
    }

    public function test_customers_can_perform_subscription_checks()
    {
        $billable = $this->createBillable();

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $this->assertTrue($billable->subscribed('main'));
        $this->assertFalse($billable->subscribed('default'));
        $this->assertFalse($billable->subscribedToPlan(2323));
        $this->assertTrue($billable->subscribedToPlan(2323, 'main'));
        $this->assertTrue($billable->onPrice(2323));
        $this->assertFalse($billable->onPrice(323));
        $this->assertFalse($billable->onTrial('main'));
        $this->assertFalse($billable->onGenericTrial());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertTrue($subscription->recurring());
        $this->assertFalse($subscription->canceled());
    }

    public function test_customers_can_check_if_they_are_on_a_generic_trial()
    {
        $billable = $this->createBillable('taylor', ['trial_ends_at' => Carbon::tomorrow()]);

        $this->assertTrue($billable->onGenericTrial());
        $this->assertTrue($billable->onTrial());
        $this->assertFalse($billable->onTrial('main'));
        $this->assertEquals($billable->trialEndsAt(), Carbon::tomorrow());
    }

    public function test_customers_can_check_if_their_subscription_is_on_trial()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_TRIALING,
            'quantity' => 1,
            'trial_ends_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($billable->subscribed('main'));
        $this->assertFalse($billable->subscribed('default'));
        $this->assertFalse($billable->subscribedToPlan(2323));
        $this->assertTrue($billable->subscribedToPlan(2323, 'main'));
        $this->assertTrue($billable->onPrice(2323));
        $this->assertFalse($billable->onPrice(323));
        $this->assertTrue($billable->onTrial('main'));
        $this->assertTrue($billable->onTrial('main', 2323));
        $this->assertFalse($billable->onTrial('main', 323));
        $this->assertFalse($billable->onGenericTrial());
        $this->assertEquals($billable->trialEndsAt('main'), Carbon::tomorrow());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
    }

    public function test_user_with_subscription_can_return_generic_trial_end_date()
    {
        $billable = $this->createBillable('taylor', ['trial_ends_at' => $tomorrow = Carbon::tomorrow()]);

        $subscription = $billable->subscriptions()->create([
            'name' => 'default',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $this->assertTrue($billable->onGenericTrial());
        $this->assertTrue($billable->onTrial());
        $this->assertFalse($subscription->onTrial());
        $this->assertEquals($tomorrow, $billable->trialEndsAt());
    }

    public function test_customers_can_check_if_their_subscription_is_on_its_grace_period()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_CANCELED,
            'quantity' => 1,
            'ends_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->canceled());
    }

    public function test_customers_can_check_if_the_grace_period_is_over()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_CANCELED,
            'quantity' => 1,
            'ends_at' => Carbon::yesterday(),
        ]);

        $this->assertFalse($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertTrue($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
    }

    public function test_customers_can_check_if_the_subscription_is_paused()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_PAUSED,
            'quantity' => 1,
        ]);

        $this->assertFalse($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->paused());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
    }

    public function test_subscriptions_can_be_on_a_paused_grace_period()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
            'paused_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
    }
}
