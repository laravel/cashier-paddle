<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;
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


    public function test_mixed_subscription_item_changes_handle_proration_correctly()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        // Create initial items for the subscription
        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_1',
            'price_id' => 'price_id_to_be_updated',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_2',
            'price_id' => 'price_id_to_be_unchanged',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_3',
            'price_id' => 'price_id_to_be_removed',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_4',
            'price_id' => 'price_id_to_be_decremented',
            'status' => 'active',
            'quantity' => 3,
        ]);

        // Prepare the update payload
        Cashier::fake([
            'subscriptions/sub_123456789' => [
                'data' => [
                    'subscription_id' => 'sub_test_123',
                    'status' => 'active',
                    "items" => [

                    ]
                ],
            ],
        ]);

        $subscription->updateItemsWithDifferentProration(
            items: [
                ['price_id' => 'price_id_to_be_updated', 'quantity' => 2],      // Quantity update 1 to 2
                ['price_id' => 'price_id_to_be_added', 'quantity' => 1],        // New item addition
                ['price_id' => 'price_id_to_be_decremented', 'quantity' => 1],  // Quantity decrease from 3 to 1
                ['price_id' => 'price_id_to_be_unchanged', 'quantity' => 1],          // no change
            ],
            additionProrationBehaviour: 'prorated_immediately',
            removalProrationBehaviour: 'full_next_billing_period'
        );

        Http::assertSentInOrder([
            // Additions and Quantity increase
            function (Request $request) {
                $data = $request->data();

                $this->assertEquals('prorated_immediately', $data['proration_billing_mode']);

                $expectedItems = [
                    ['price_id' => 'price_id_to_be_updated', 'quantity' => 2],      // Quantity update 1 to 2
                    ['price_id' => 'price_id_to_be_added', 'quantity' => 1],        // New item addition
                    ['price_id' => 'price_id_to_be_decremented', 'quantity' => 3],  // unchanged
                    ['price_id' => 'price_id_to_be_unchanged', 'quantity' => 1],    // unchanged
                ];

                foreach ($expectedItems as $expected) {
                    $this->assertTrue(
                        collect($data['items'])->contains(
                            fn($actual) => $actual['price_id'] === $expected['price_id'] && $actual['quantity'] === $expected['quantity']
                        )
                    );
                }

                return true;
            },

            // Removals and Quantity decrease
            function (Request $request) {
                $data = $request->data();

                $this->assertEquals('full_next_billing_period', $data['proration_billing_mode']);

                $expectedItems = [
                    ['price_id' => 'price_id_to_be_updated', 'quantity' => 2],      // unchanged
                    ['price_id' => 'price_id_to_be_added', 'quantity' => 1],        // unchanged
                    ['price_id' => 'price_id_to_be_decremented', 'quantity' => 1],  // decremented
                    ['price_id' => 'price_id_to_be_unchanged', 'quantity' => 1],    // unchanged
                ];


                foreach ($expectedItems as $expected) {
                    $this->assertTrue(
                        collect($data['items'])->contains(
                            fn($actual) => $actual['price_id'] === $expected['price_id'] && $actual['quantity'] === $expected['quantity']
                        )
                    );
                }

                return true;
            }
        ]);
    }

    public function test_proration_updates_without_removals_in_payload()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        // Create initial items for the subscription
        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_1',
            'price_id' => 'price_id_to_be_updated',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_2',
            'price_id' => 'price_id_to_be_unchanged',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_3',
            'price_id' => 'price_id_to_be_removed',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_4',
            'price_id' => 'price_id_to_be_decremented',
            'status' => 'active',
            'quantity' => 3,
        ]);

        // Prepare the update payload
        Cashier::fake([
            'subscriptions/sub_123456789' => [
                'data' => [
                    'subscription_id' => 'sub_test_123',
                    'status' => 'active',
                    "items" => [

                    ]
                ],
            ],
        ]);

        $subscription->updateItemsWithDifferentProration(
            items: [
                ['price_id' => 'price_id_to_be_updated', 'quantity' => 2],      // Quantity update 1 to 2
                ['price_id' => 'price_id_to_be_added', 'quantity' => 1],        // New item addition
            ],
            additionProrationBehaviour: 'prorated_next_billing_period',
            removalProrationBehaviour: 'full_next_billing_period'
        );

        Http::assertSentInOrder([
            // Additions and Quantity increase
            function (Request $request) {
                $data = $request->data();

                $this->assertEquals('prorated_next_billing_period', $data['proration_billing_mode']);

                $expectedItems = [
                    ['price_id' => 'price_id_to_be_updated', 'quantity' => 2],      // Quantity update 1 to 2
                    ['price_id' => 'price_id_to_be_added', 'quantity' => 1],        // New item addition
                    ['price_id' => 'price_id_to_be_decremented', 'quantity' => 3],  // unchanged
                    ['price_id' => 'price_id_to_be_unchanged', 'quantity' => 1],    // unchanged
                ];

                foreach ($expectedItems as $expected) {
                    $this->assertTrue(
                        collect($data['items'])->contains(
                            fn($actual) => $actual['price_id'] === $expected['price_id'] && $actual['quantity'] === $expected['quantity']
                        )
                    );
                }

                return true;
            },

            // Removals and Quantity decrease
            function (Request $request) {
                $data = $request->data();

                $this->assertEquals('full_next_billing_period', $data['proration_billing_mode']);

                $expectedItems = [
                    ['price_id' => 'price_id_to_be_updated', 'quantity' => 2],      // unchanged
                    ['price_id' => 'price_id_to_be_added', 'quantity' => 1],        // unchanged
                    // Removed item should not be present in the payload
                    // ['price_id' => 'price_id_to_be_decremented', 'quantity' => 1],
                    // ['price_id' => 'price_id_to_be_unchanged', 'quantity' => 1],
                ];


                foreach ($expectedItems as $expected) {
                    $this->assertTrue(
                        collect($data['items'])->contains(
                            fn($actual) => $actual['price_id'] === $expected['price_id'] && $actual['quantity'] === $expected['quantity']
                        )
                    );
                }

                return true;
            }
        ]);
    }

    public function test_proration_handles_item_quantity_increments_and_decrements()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        // Create initial items for the subscription
        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_1',
            'price_id' => 'price_id_to_be_incremented',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'subscription_id' => $subscription->id,
            'product_id' => 'pro_product_4',
            'price_id' => 'price_id_to_be_decremented',
            'status' => 'active',
            'quantity' => 10,
        ]);

        // Prepare the update payload
        Cashier::fake([
            'subscriptions/sub_123456789' => [
                'data' => [
                    'subscription_id' => 'sub_test_123',
                    'status' => 'active',
                    "items" => []
                ],
            ],
        ]);

        $subscription->updateItemsWithDifferentProration(
            items: [
                ['price_id' => 'price_id_to_be_incremented', 'quantity' => 5],  // Quantity update 1 to 5
                ['price_id' => 'price_id_to_be_decremented', 'quantity' => 4], // Quantity decrease from 10 to 4
            ],
            additionProrationBehaviour: 'prorated_immediately',
            removalProrationBehaviour: 'full_next_billing_period'
        );

        Http::assertSentInOrder([
            // Additions and Quantity increase
            function (Request $request) {
                $data = $request->data();

                $this->assertEquals('prorated_immediately', $data['proration_billing_mode']);

                $expectedItems = [
                    ['price_id' => 'price_id_to_be_incremented', 'quantity' => 5],  // Quantity update 1 to 5
                    ['price_id' => 'price_id_to_be_decremented', 'quantity' => 10], // Unchanged
                ];

                foreach ($expectedItems as $expected) {
                    $this->assertTrue(
                        collect($data['items'])->contains(
                            fn($actual) => $actual['price_id'] === $expected['price_id'] && $actual['quantity'] === $expected['quantity']
                        )
                    );
                }

                return true;
            },

            // Removals and Quantity decrease
            function (Request $request) {
                $data = $request->data();

                $this->assertEquals('full_next_billing_period', $data['proration_billing_mode']);

                $expectedItems = [
                    ['price_id' => 'price_id_to_be_incremented', 'quantity' => 5],  // Quantity update 1 to 5
                    ['price_id' => 'price_id_to_be_decremented', 'quantity' => 4], // Quantity decrease from 10 to 4
                ];


                foreach ($expectedItems as $expected) {
                    $this->assertTrue(
                        collect($data['items'])->contains(
                            fn($actual) => $actual['price_id'] === $expected['price_id'] && $actual['quantity'] === $expected['quantity']
                        )
                    );
                }

                return true;
            }
        ]);
    }

}
