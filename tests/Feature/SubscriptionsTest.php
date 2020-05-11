<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Laravel\Paddle\Subscription;

class SubscriptionsTest extends FeatureTestCase
{
    public function test_customers_can_perform_subscription_checks()
    {
        $customer = $this->createCustomer();

        $subscription = $customer->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertTrue($customer->subscribed('main'));
        $this->assertFalse($customer->subscribed('default'));
        $this->assertFalse($customer->subscribedToPlan(2323));
        $this->assertTrue($customer->subscribedToPlan(2323, 'main'));
        $this->assertTrue($customer->onPlan(2323));
        $this->assertFalse($customer->onPlan(323));
        $this->assertFalse($customer->onTrial('main'));
        $this->assertFalse($customer->onGenericTrial());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertTrue($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_customers_can_check_if_they_are_on_a_generic_trial()
    {
        $customer = $this->createCustomer('taylor', ['trial_ends_at' => Carbon::tomorrow()]);

        $this->assertTrue($customer->onGenericTrial());
        $this->assertTrue($customer->onTrial());
        $this->assertFalse($customer->onTrial('main'));
    }

    public function test_customers_can_check_if_their_subscription_is_on_trial()
    {
        $customer = $this->createCustomer('taylor');

        $subscription = $customer->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => 'trialing',
            'quantity' => 1,
            'trial_ends_at' => Carbon::tomorrow(),
        ]);

        $this->assertTrue($customer->subscribed('main'));
        $this->assertFalse($customer->subscribed('default'));
        $this->assertFalse($customer->subscribedToPlan(2323));
        $this->assertTrue($customer->subscribedToPlan(2323, 'main'));
        $this->assertTrue($customer->onPlan(2323));
        $this->assertFalse($customer->onPlan(323));
        $this->assertTrue($customer->onTrial('main'));
        $this->assertTrue($customer->onTrial('main', 2323));
        $this->assertFalse($customer->onTrial('main', 323));
        $this->assertFalse($customer->onGenericTrial());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_customers_can_check_if_their_subscription_is_cancelled()
    {
        $customer = $this->createCustomer('taylor');

        $subscription = $customer->subscriptions()->create([
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
        $this->assertTrue($subscription->cancelled());
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertFalse($subscription->ended());
    }

    public function test_customers_can_check_if_the_grace_period_is_over()
    {
        $customer = $this->createCustomer('taylor');

        $subscription = $customer->subscriptions()->create([
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
        $this->assertTrue($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->recurring());
        $this->assertTrue($subscription->ended());
    }
}
