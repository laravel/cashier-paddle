<?php

namespace Tests\Feature;

class CustomerTest extends FeatureTestCase
{
    public function test_billable_models_can_create_a_customer_record()
    {
        $user = $this->createUser();

        $customer = $user->createAsCustomer(['trial_ends_at' => $trialEndsAt = now()->addDays(15)]);

        $this->assertSame($trialEndsAt->timestamp, $customer->trial_ends_at->timestamp);
        $this->assertTrue($user->onGenericTrial());
    }

    public function test_billable_models_without_having_a_customer_record_can_still_use_some_methods()
    {
        $user = $this->createUser();

        $this->assertFalse($user->onTrial());
        $this->assertFalse($user->onGenericTrial());
        $this->assertFalse($user->onPlan(123));
        $this->assertFalse($user->subscribed());
        $this->assertFalse($user->subscribedToPlan(123));
        $this->assertEmpty($user->transactions());
        $this->assertEmpty($user->subscriptions());
        $this->assertNull($user->subscription());
    }
}
