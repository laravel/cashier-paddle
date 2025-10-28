<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Laravel\Paddle\Subscription;

class SubscriptionsTest extends FeatureTestCase
{
    public function test_customers_can_perform_subscription_checks()
    {
        $billable = $this->createBillable();

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertTrue($billable->subscribed('main'));
        $this->assertFalse($billable->subscribed('default'));
        $this->assertFalse($billable->subscribedToPrice('pri_123456789'));
        $this->assertTrue($billable->subscribedToPrice('pri_123456789', 'main'));
        $this->assertTrue($billable->onPrice('pri_123456789'));
        $this->assertFalse($billable->onPrice('pri_987'));
        $this->assertFalse($billable->onTrial('main'));
        $this->assertFalse($billable->onGenericTrial());

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->onGracePeriod());
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
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => Carbon::tomorrow(),
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'trialing',
            'quantity' => 1,
        ]);

        $this->assertTrue($billable->subscribed('main'));
        $this->assertFalse($billable->subscribed('default'));
        $this->assertFalse($billable->subscribedToPrice('pri_123456789'));
        $this->assertTrue($billable->subscribedToPrice('pri_123456789', 'main'));
        $this->assertTrue($billable->onPrice('pri_123456789'));
        $this->assertFalse($billable->onPrice('pri_987'));
        $this->assertTrue($billable->onTrial('main'));
        $this->assertTrue($billable->onTrial('main', 'pri_123456789'));
        $this->assertFalse($billable->onTrial('main', 'pri_987'));
        $this->assertFalse($billable->onGenericTrial());
        $this->assertEquals($billable->trialEndsAt('main'), Carbon::tomorrow());

        $this->assertTrue($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function test_user_with_subscription_can_return_generic_trial_end_date()
    {
        $billable = $this->createBillable('taylor', ['trial_ends_at' => $tomorrow = Carbon::tomorrow()]);

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
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
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => Carbon::tomorrow(),
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertFalse($subscription->canceled());
    }

    public function test_customers_can_check_if_the_grace_period_is_over()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_CANCELED,
            'ends_at' => Carbon::yesterday(),
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertFalse($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertTrue($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function test_customers_can_check_if_the_subscription_is_paused()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_PAUSED,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertFalse($subscription->valid());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->paused());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function test_subscriptions_can_be_on_a_paused_grace_period()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paused_at' => Carbon::tomorrow(),
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->paused());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function test_subscription_method_prioritizes_valid_subscriptions_over_invalid_ones()
    {
        $billable = $this->createBillable('taylor');

        // Create a canceled subscription first
        $canceledSubscription = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_canceled',
            'status' => Subscription::STATUS_CANCELED,
            'ends_at' => Carbon::yesterday(),
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $canceledSubscription->items()->create([
            'subscription_id' => $canceledSubscription->id,
            'product_id' => 'pro_123',
            'price_id' => 'pri_123',
            'status' => 'canceled',
            'quantity' => 1,
        ]);

        // Create an active subscription
        $activeSubscription = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_active',
            'status' => Subscription::STATUS_ACTIVE,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $activeSubscription->items()->create([
            'subscription_id' => $activeSubscription->id,
            'product_id' => 'pro_456',
            'price_id' => 'pri_456',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Refresh to reload subscriptions collection
        $billable = $billable->fresh();

        // The subscription() method should return the active one, not the canceled one
        $subscription = $billable->subscription('default');

        $this->assertNotNull($subscription);
        $this->assertEquals('sub_active', $subscription->paddle_id);
        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
    }

    public function test_subscription_method_prioritizes_trialing_subscriptions_over_canceled_ones()
    {
        $billable = $this->createBillable('taylor');

        // Create a canceled subscription
        $canceledSubscription = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_canceled',
            'status' => Subscription::STATUS_CANCELED,
            'ends_at' => Carbon::yesterday(),
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $canceledSubscription->items()->create([
            'subscription_id' => $canceledSubscription->id,
            'product_id' => 'pro_123',
            'price_id' => 'pri_123',
            'status' => 'canceled',
            'quantity' => 1,
        ]);

        // Create a trialing subscription
        $trialingSubscription = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_trialing',
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => Carbon::tomorrow(),
            'created_at' => Carbon::now()->subDay(),
        ]);

        $trialingSubscription->items()->create([
            'subscription_id' => $trialingSubscription->id,
            'product_id' => 'pro_789',
            'price_id' => 'pri_789',
            'status' => 'trialing',
            'quantity' => 1,
        ]);

        // Refresh to reload subscriptions collection
        $billable = $billable->fresh();

        // The subscription() method should return the trialing one
        $subscription = $billable->subscription('default');

        $this->assertNotNull($subscription);
        $this->assertEquals('sub_trialing', $subscription->paddle_id);
        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->onTrial());
    }

    public function test_subscription_method_falls_back_to_first_subscription_when_none_are_valid()
    {
        $billable = $this->createBillable('taylor');

        // Create multiple canceled subscriptions
        $firstCanceled = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_first_canceled',
            'status' => Subscription::STATUS_CANCELED,
            'ends_at' => Carbon::yesterday(),
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $firstCanceled->items()->create([
            'subscription_id' => $firstCanceled->id,
            'product_id' => 'pro_111',
            'price_id' => 'pri_111',
            'status' => 'canceled',
            'quantity' => 1,
        ]);

        $secondCanceled = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_second_canceled',
            'status' => Subscription::STATUS_CANCELED,
            'ends_at' => Carbon::yesterday(),
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $secondCanceled->items()->create([
            'subscription_id' => $secondCanceled->id,
            'product_id' => 'pro_222',
            'price_id' => 'pri_222',
            'status' => 'canceled',
            'quantity' => 1,
        ]);

        // Refresh to reload subscriptions collection
        $billable = $billable->fresh();

        // When no valid subscriptions exist, it should fall back to first one
        // Since subscriptions are ordered by created_at DESC, the second_canceled should be first
        $subscription = $billable->subscription('default');

        $this->assertNotNull($subscription);
        $this->assertEquals('sub_second_canceled', $subscription->paddle_id);
        $this->assertFalse($subscription->valid());
    }

    public function test_subscription_method_returns_null_when_no_subscriptions_of_type_exist()
    {
        $billable = $this->createBillable('taylor');

        // Create a subscription of a different type
        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_main',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_123',
            'price_id' => 'pri_123',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Refresh to reload subscriptions collection
        $billable = $billable->fresh();

        // Requesting a subscription of type 'default' should return null
        $result = $billable->subscription('default');

        $this->assertNull($result);
    }

    public function test_subscription_method_returns_first_valid_when_multiple_valid_subscriptions_exist()
    {
        $billable = $this->createBillable('taylor');

        // Create first active subscription
        $firstActive = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_first_active',
            'status' => Subscription::STATUS_ACTIVE,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $firstActive->items()->create([
            'subscription_id' => $firstActive->id,
            'product_id' => 'pro_111',
            'price_id' => 'pri_111',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Create second active subscription (newer)
        $secondActive = $billable->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_second_active',
            'status' => Subscription::STATUS_ACTIVE,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $secondActive->items()->create([
            'subscription_id' => $secondActive->id,
            'product_id' => 'pro_222',
            'price_id' => 'pri_222',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Refresh to reload subscriptions collection
        $billable = $billable->fresh();

        // Should return the first valid subscription it finds
        // Since subscriptions are ordered by created_at DESC, it should return the second_active
        $subscription = $billable->subscription('default');

        $this->assertNotNull($subscription);
        $this->assertEquals('sub_second_active', $subscription->paddle_id);
        $this->assertTrue($subscription->valid());
    }
}
