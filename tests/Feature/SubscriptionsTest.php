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

        $this->expectExceptionObject(new LogicException('Paddle does not allow swapping plans while on trial.'));

        $subscription->swap(123);
    }

    public function test_customers_can_perform_subscription_checks()
    {
        $billable = $this->createBillable();

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertTrue($billable->subscribed('main'));
        $this->assertFalse($billable->subscribed('default'));
        $this->assertFalse($billable->subscribedToPlan(2323));
        $this->assertTrue($billable->subscribedToPlan(2323, 'main'));
        $this->assertTrue($billable->onPlan(2323));
        $this->assertFalse($billable->onPlan(323));
        $this->assertFalse($billable->onTrial('main'));
        $this->assertFalse($billable->onGenericTrial());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertTrue($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_customers_can_check_if_they_are_on_a_generic_trial()
    {
        $billable = $this->createBillable('taylor', ['trial_ends_at' => Carbon::tomorrow()]);

        $this->assertTrue($billable->onGenericTrial());
        $this->assertTrue($billable->onTrial());
        $this->assertFalse($billable->onTrial('main'));
    }

    public function test_customers_can_check_if_their_subscription_is_on_trial()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => 'trialing',
            'quantity' => 1,
            'trial_ends_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($billable->subscribed('main'));
        $this->assertFalse($billable->subscribed('default'));
        $this->assertFalse($billable->subscribedToPlan(2323));
        $this->assertTrue($billable->subscribedToPlan(2323, 'main'));
        $this->assertTrue($billable->onPlan(2323));
        $this->assertFalse($billable->onPlan(323));
        $this->assertTrue($billable->onTrial('main'));
        $this->assertTrue($billable->onTrial('main', 2323));
        $this->assertFalse($billable->onTrial('main', 323));
        $this->assertFalse($billable->onGenericTrial());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_customers_can_check_if_their_subscription_is_cancelled()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_DELETED,
            'quantity' => 1,
            'ends_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertTrue($subscription->cancelled());
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_customers_can_check_if_the_grace_period_is_over()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_DELETED,
            'quantity' => 1,
            'ends_at' => Carbon::yesterday(),
        ]);

        $this->assertFalse($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertTrue($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertTrue($subscription->ended());
    }

    public function test_customers_can_check_if_the_subscription_is_paused()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_PAUSED,
            'quantity' => 1,
        ]);

        $this->assertFalse($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->paused());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_subscriptions_can_be_on_a_paused_grace_period()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
            'paused_from' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_subscriptions_can_retrieve_their_payment_info()
    {
        if (! isset($_SERVER['PADDLE_TEST_PLAN_HOBBY'], $_SERVER['PADDLE_TEST_SUBSCRIPTION'])) {
            $this->markTestSkipped('Plan and/or subscription not configured.');
        }

        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => $_SERVER['PADDLE_TEST_SUBSCRIPTION'],
            'paddle_plan' => $_SERVER['PADDLE_TEST_PLAN_HOBBY'],
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $this->assertSame('john@example.com', $subscription->paddleEmail());
        $this->assertSame('card', $subscription->paymentMethod());
        $this->assertSame('master', $subscription->cardBrand());
        $this->assertSame('4050', $subscription->cardLastFour());
    }
}
