<?php

namespace Tests\Unit;

use Laravel\Paddle\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function test_it_can_determine_if_the_subscription_is_on_trial()
    {
        $subscription = new Subscription();
        $subscription->setDateFormat('Y-m-d H:i:s');
        $subscription->trial_ends_at = now()->addDay();

        $this->assertTrue($subscription->onTrial());

        $subscription = new Subscription();
        $subscription->setDateFormat('Y-m-d H:i:s');
        $subscription->trial_ends_at = now()->subDay();

        $this->assertFalse($subscription->onTrial());
    }

    public function test_it_can_determine_if_a_trial_has_expired()
    {
        $subscription = new Subscription();
        $subscription->setDateFormat('Y-m-d H:i:s');
        $subscription->trial_ends_at = now()->subDay();

        $this->assertTrue($subscription->hasExpiredTrial());

        $subscription = new Subscription();
        $subscription->setDateFormat('Y-m-d H:i:s');
        $subscription->trial_ends_at = now()->addDay();

        $this->assertFalse($subscription->hasExpiredTrial());
    }
}
