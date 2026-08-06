<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Events\TransactionUpdated;
use Laravel\Paddle\Subscription;
use Laravel\Paddle\Transaction;

class WebhooksTest extends FeatureTestCase
{
    public function test_it_can_handle_a_transaction_completed_event()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction_completed',
            'occurred_at' => $billedAt = now()->addDay()->format('Y-m-d H:i:s'),
            'data' => [
                'id' => 'txn_123456789',
                'customer_id' => 'cus_123456789',
                'status' => 'completed',
                'subscription_id' => 'sub_123456789',
                'invoice_number' => 'foo',
                'currency_code' => 'EUR',
                'details' => [
                    'totals' => [
                        'total' => '1255',
                        'tax' => '434',
                    ],
                ],
                'billed_at' => $billedAt,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'cus_123456789',
        ]);

        $this->assertDatabaseHas('transactions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_subscription_id' => 'sub_123456789',
            'status' => 'completed',
            'total' => '1255',
            'tax' => '434',
            'currency' => 'EUR',
            'billed_at' => $billedAt,
        ]);

        Cashier::assertTransactionCompleted(function (TransactionCompleted $event) use ($user) {
            return $event->billable->id === $user->id && $event->transaction->paddle_id === 'txn_123456789';
        });
    }

    public function test_it_can_handle_a_transaction_updated_event()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => null,
            'status' => Transaction::STATUS_BILLED,
            'total' => '3070166',
            'tax' => '250266',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'test-123456789',
                'status' => Transaction::STATUS_CANCELED,
                'details' => [
                    'totals' => [
                        'total' => '1500',
                        'tax' => '300',
                    ],
                ],
                'billed_at' => $billedAt = now()->addDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'txn_123456789',
            'status' => Transaction::STATUS_CANCELED,
            'total' => '1500',
            'tax' => '300',
            'currency' => 'USD',
            'billed_at' => $billedAt,
        ]);

        Cashier::assertTransactionUpdated(function (TransactionUpdated $event) {
            return $event->transaction->paddle_id === 'txn_123456789';
        });
    }

    public function test_an_older_paid_update_does_not_overwrite_a_completed_transaction()
    {
        Cashier::fake();

        $this->createBillable();

        $completedAt = now('UTC');
        $billedAt = $completedAt->copy()->subMinute()->format('Y-m-d H:i:s');

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.completed',
            'occurred_at' => $completedAt->format('Y-m-d\TH:i:s.u\Z'),
            'data' => [
                'id' => 'txn_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Transaction::STATUS_COMPLETED,
                'subscription_id' => 'sub_123456789',
                'invoice_number' => 'test-123456789',
                'currency_code' => 'USD',
                'details' => [
                    'totals' => [
                        'total' => '1500',
                        'tax' => '300',
                    ],
                ],
                'billed_at' => $billedAt,
            ],
        ])->assertOk();

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'occurred_at' => $completedAt->copy()->subSecond()->format('Y-m-d\TH:i:s.u\Z'),
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => null,
                'status' => Transaction::STATUS_PAID,
                'details' => [
                    'totals' => [
                        'total' => '1255',
                        'tax' => '250',
                    ],
                ],
                'billed_at' => $billedAt,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'invoice_number' => 'test-123456789',
            'status' => Transaction::STATUS_COMPLETED,
            'total' => '1500',
            'tax' => '300',
        ]);

        Event::assertNotDispatched(TransactionUpdated::class);
    }

    public function test_it_can_handle_a_subscription_created_event()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_created',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_ACTIVE,
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'active',
                        'quantity' => 1,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'cus_123456789',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
        ]);

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        Cashier::assertSubscriptionCreated(function (SubscriptionCreated $event) use ($user) {
            return $event->billable->id === $user->id && $event->subscription->paddle_id === 'sub_123456789';
        });
    }

    public function test_it_can_handle_a_cardless_trial_subscription_created_event()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_created',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_TRIALING,
                'next_billed_at' => null,
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'trialing',
                        'quantity' => 1,
                        'trial_dates' => [
                            'starts_at' => now('UTC')->format('Y-m-d H:i:s'),
                            'ends_at' => ($trialEndsAt = now('UTC')->addDays(14))->format('Y-m-d H:i:s'),
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    public function test_it_can_handle_a_cardless_trial_subscription_updated_event()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_TRIALING,
        ]);

        $subscription->items()->create([
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'trialing',
            'quantity' => 1,
        ]);

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_TRIALING,
                'next_billed_at' => null,
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'trialing',
                        'quantity' => 1,
                        'trial_dates' => [
                            'starts_at' => now('UTC')->format('Y-m-d H:i:s'),
                            'ends_at' => ($trialEndsAt = now('UTC')->addDays(14))->format('Y-m-d H:i:s'),
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    public function test_it_can_handle_a_card_required_trial_subscription_created_event()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_created',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_TRIALING,
                'next_billed_at' => ($trialEndsAt = now('UTC')->addDays(14))->format('Y-m-d H:i:s'),
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'trialing',
                        'quantity' => 1,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    public function test_it_can_handle_a_duplicated_subscription_created_event()
    {
        Cashier::fake();

        $user = $this->createBillable();

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('paddle/webhook', [
                'event_type' => 'subscription_created',
                'data' => [
                    'id' => 'sub_123456789',
                    'customer_id' => 'cus_123456789',
                    'status' => Subscription::STATUS_ACTIVE,
                    'custom_data' => [
                        'subscription_type' => 'main',
                    ],
                    'items' => [
                        [
                            'price' => [
                                'id' => 'pri_123456789',
                                'product_id' => 'pro_123456789',
                            ],
                            'status' => 'active',
                            'quantity' => 1,
                        ],
                    ],
                ],
            ])->assertOk();
        }

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'cus_123456789',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
        ]);

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        Cashier::assertSubscriptionCreated(function (SubscriptionCreated $event) use ($user) {
            return $event->billable->id === $user->id && $event->subscription->paddle_id === 'sub_123456789';
        });
    }

    public function test_it_can_handle_a_subscription_updated_event()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
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

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_PAUSED,
                'paused_at' => ($date = now('UTC')->addDays(5))->format('Y-m-d H:i:s'),
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'active',
                        'quantity' => 3,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_PAUSED,
            'paused_at' => $date,
        ]);

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => 1,
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 3,
        ]);

        Cashier::assertSubscriptionUpdated(function (SubscriptionUpdated $event) {
            return $event->subscription->paddle_id === 'sub_123456789';
        });
    }

    public function test_it_can_handle_a_subscription_canceled_event()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
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

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_canceled',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_CANCELED,
                'canceled_at' => ($date = now('UTC')->addDays(5))->format('Y-m-d H:i:s'),
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'active',
                        'quantity' => 1,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_CANCELED,
            'ends_at' => $date,
        ]);

        Cashier::assertSubscriptionCanceled(function (SubscriptionCanceled $event) {
            return $event->subscription->paddle_id === 'sub_123456789';
        });
    }

    public function test_subscription_created_event_without_a_matching_customer_is_ignored()
    {
        Cashier::fake();

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_created',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_987654321',
                'status' => Subscription::STATUS_ACTIVE,
                'custom_data' => [
                    'subscription_type' => 'main',
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_123456789',
                            'product_id' => 'pro_123456789',
                        ],
                        'status' => 'active',
                        'quantity' => 1,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('customers', [
            'paddle_id' => 'cus_987654321',
        ]);

        $this->assertDatabaseMissing('subscriptions', [
            'paddle_id' => 'sub_123456789',
        ]);

        Cashier::assertSubscriptionNotCreated();
    }
}
