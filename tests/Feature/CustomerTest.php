<?php

namespace Tests\Feature;

use Laravel\Paddle\Cashier;

class CustomerTest extends FeatureTestCase
{
    public function test_billable_models_can_create_a_customer_record()
    {
        $user = $this->createUser();

        Cashier::fake([
            'customers*' => [
                'data' => [[
                    'id' => 'cus_123456789',
                    'name' => $user->name,
                    'email' => $user->email,
                ]],
            ],
        ]);

        $customer = $user->createAsCustomer(['trial_ends_at' => $trialEndsAt = now()->addDays(15)]);

        $this->assertSame($trialEndsAt->timestamp, $customer->trial_ends_at->timestamp);
        $this->assertSame($trialEndsAt->timestamp, $user->trialEndsAt()->timestamp);
        $this->assertTrue($user->onGenericTrial());
    }

    public function test_billable_models_without_having_a_customer_record_can_still_use_some_methods()
    {
        $user = $this->createUser();

        $this->assertFalse($user->onTrial());
        $this->assertFalse($user->onGenericTrial());
        $this->assertFalse($user->onPrice(123));
        $this->assertFalse($user->subscribed());
        $this->assertFalse($user->subscribedToPrice(123));
        $this->assertEmpty($user->subscriptions);
        $this->assertEmpty($user->transactions);
        $this->assertNull($user->subscription());
    }

    public function test_trial_ends_at_works_if_generic_trial_is_expired()
    {
        $user = $this->createUser();

        Cashier::fake([
            'customers*' => [
                'data' => [[
                    'id' => 'cus_123456789',
                    'name' => $user->name,
                    'email' => $user->email,
                ]],
            ],
        ]);

        $user->createAsCustomer(['trial_ends_at' => $trialEndsAt = now()->subDays(15)]);

        $this->assertSame($trialEndsAt->timestamp, $user->trialEndsAt()->timestamp);
    }
}
