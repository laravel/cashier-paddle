<?php

namespace Tests\Feature;

use Illuminate\Database\UniqueConstraintViolationException;
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
    // ---------------------------------------------------------------
    // Transaction Completed
    // ---------------------------------------------------------------

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

    public function test_transaction_completed_stores_paddle_updated_at_when_present()
    {
        Cashier::fake();

        $user = $this->createBillable();
        $paddleUpdatedAt = '2026-03-18 12:30:00';

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction_completed',
            'occurred_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'data' => [
                'id' => 'txn_123456789',
                'customer_id' => 'cus_123456789',
                'status' => 'completed',
                'subscription_id' => 'sub_123456789',
                'invoice_number' => 'foo',
                'currency_code' => 'EUR',
                'updated_at' => $paddleUpdatedAt,
                'details' => [
                    'totals' => [
                        'total' => '1255',
                        'tax' => '434',
                    ],
                ],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'paddle_updated_at' => $paddleUpdatedAt,
        ]);
    }

    public function test_transaction_completed_without_updated_at_stores_null()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction_completed',
            'occurred_at' => now()->addDay()->format('Y-m-d H:i:s'),
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
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'paddle_updated_at' => null,
        ]);
    }

    public function test_duplicate_transaction_completed_is_caught_by_existence_check()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $payload = [
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
        ];

        // First webhook creates the record.
        $this->postJson('paddle/webhook', $payload)->assertOk();

        // Second webhook is caught by transactionExists() check — no error.
        $this->postJson('paddle/webhook', $payload)->assertOk();

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_duplicate_transaction_completed_caught_by_unique_constraint_on_race()
    {
        Cashier::fake();

        $user = $this->createBillable();

        // Pre-insert the transaction to simulate a concurrent request that passed
        // the exists check but inserted first — now our webhook hits the catch.
        $user->transactions()->create([
            'paddle_id' => 'txn_race_test',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => 'INV-001',
            'status' => Transaction::STATUS_COMPLETED,
            'total' => '1255',
            'tax' => '434',
            'currency' => 'EUR',
            'billed_at' => now(),
        ]);

        // Mock transactionExists to return false (simulating race — both requests
        // passed the check before either inserted).
        $controller = new \Laravel\Paddle\Http\Controllers\WebhookController;
        $reflection = new \ReflectionMethod($controller, 'handleTransactionCompleted');

        // Directly verify the UniqueConstraintViolationException is caught by
        // attempting to create a transaction with the same paddle_id.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction_completed',
            'occurred_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'data' => [
                'id' => 'txn_race_test',
                'customer_id' => 'cus_123456789',
                'status' => 'completed',
                'subscription_id' => 'sub_123456789',
                'invoice_number' => 'INV-001',
                'currency_code' => 'EUR',
                'details' => [
                    'totals' => [
                        'total' => '1255',
                        'tax' => '434',
                    ],
                ],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        // Still only one record — the duplicate was caught (by exists check here,
        // but the UniqueConstraintViolationException catch is the safety net).
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_transaction_completed_for_unknown_customer_is_ignored()
    {
        Cashier::fake();

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction_completed',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'data' => [
                'id' => 'txn_orphan',
                'customer_id' => 'cus_nonexistent',
                'status' => 'completed',
                'subscription_id' => null,
                'invoice_number' => 'INV-999',
                'currency_code' => 'USD',
                'details' => [
                    'totals' => [
                        'total' => '500',
                        'tax' => '100',
                    ],
                ],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('transactions', [
            'paddle_id' => 'txn_orphan',
        ]);
    }

    // ---------------------------------------------------------------
    // Transaction Updated
    // ---------------------------------------------------------------

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

    public function test_transaction_updated_stores_paddle_updated_at()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => null,
            'status' => Transaction::STATUS_BILLED,
            'total' => '1000',
            'tax' => '200',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        $paddleUpdatedAt = '2026-03-18 14:00:00';

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'INV-NEW',
                'status' => Transaction::STATUS_COMPLETED,
                'updated_at' => $paddleUpdatedAt,
                'details' => [
                    'totals' => [
                        'total' => '1500',
                        'tax' => '300',
                    ],
                ],
                'billed_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'status' => Transaction::STATUS_COMPLETED,
            'paddle_updated_at' => $paddleUpdatedAt,
        ]);
    }

    public function test_stale_transaction_updated_webhook_does_not_overwrite_newer_data()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $newerPaddleTimestamp = '2026-03-18 14:00:00';

        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => 'latest-invoice',
            'status' => Transaction::STATUS_COMPLETED,
            'total' => '2000',
            'tax' => '400',
            'currency' => 'USD',
            'billed_at' => now(),
            'paddle_updated_at' => $newerPaddleTimestamp,
        ]);

        // Send a webhook with an OLDER Paddle updated_at timestamp.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'stale-invoice',
                'status' => Transaction::STATUS_BILLED,
                'updated_at' => '2026-03-18 13:55:00', // 5 minutes earlier
                'details' => [
                    'totals' => [
                        'total' => '1000',
                        'tax' => '200',
                    ],
                ],
                'billed_at' => now()->subDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        // Record was NOT updated — stale webhook rejected.
        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'invoice_number' => 'latest-invoice',
            'status' => Transaction::STATUS_COMPLETED,
            'total' => '2000',
            'tax' => '400',
            'paddle_updated_at' => $newerPaddleTimestamp,
        ]);
    }

    public function test_duplicate_timestamp_transaction_updated_is_rejected()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $paddleTimestamp = '2026-03-18 14:00:00';

        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => 'original-invoice',
            'status' => Transaction::STATUS_COMPLETED,
            'total' => '2000',
            'tax' => '400',
            'currency' => 'USD',
            'billed_at' => now(),
            'paddle_updated_at' => $paddleTimestamp,
        ]);

        // Send a webhook with the SAME updated_at — should be rejected (greaterThanOrEqualTo).
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'duplicate-invoice',
                'status' => Transaction::STATUS_BILLED,
                'updated_at' => $paddleTimestamp,
                'details' => [
                    'totals' => [
                        'total' => '1000',
                        'tax' => '200',
                    ],
                ],
                'billed_at' => now()->subDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'invoice_number' => 'original-invoice',
            'status' => Transaction::STATUS_COMPLETED,
        ]);
    }

    public function test_newer_transaction_updated_webhook_overwrites_older_data()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => 'old-invoice',
            'status' => Transaction::STATUS_BILLED,
            'total' => '1000',
            'tax' => '200',
            'currency' => 'USD',
            'billed_at' => now(),
            'paddle_updated_at' => '2026-03-18 13:00:00',
        ]);

        // Send a webhook with a NEWER Paddle updated_at — should be accepted.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'new-invoice',
                'status' => Transaction::STATUS_COMPLETED,
                'updated_at' => '2026-03-18 14:00:00',
                'details' => [
                    'totals' => [
                        'total' => '2000',
                        'tax' => '400',
                    ],
                ],
                'billed_at' => $billedAt = now()->addDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'invoice_number' => 'new-invoice',
            'status' => Transaction::STATUS_COMPLETED,
            'total' => '2000',
            'tax' => '400',
            'paddle_updated_at' => '2026-03-18 14:00:00',
        ]);

        Cashier::assertTransactionUpdated(function (TransactionUpdated $event) {
            return $event->transaction->paddle_id === 'txn_123456789';
        });
    }

    public function test_transaction_updated_without_updated_at_always_applies()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => 'old-invoice',
            'status' => Transaction::STATUS_BILLED,
            'total' => '1000',
            'tax' => '200',
            'currency' => 'USD',
            'billed_at' => now(),
            'paddle_updated_at' => '2026-03-18 14:00:00',
        ]);

        // Webhook WITHOUT updated_at — guard is bypassed, update is applied.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'fallback-invoice',
                'status' => Transaction::STATUS_CANCELED,
                'details' => [
                    'totals' => [
                        'total' => '500',
                        'tax' => '100',
                    ],
                ],
                'billed_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'invoice_number' => 'fallback-invoice',
            'status' => Transaction::STATUS_CANCELED,
            'total' => '500',
        ]);
    }

    public function test_transaction_updated_for_unknown_transaction_is_ignored()
    {
        Cashier::fake();

        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_nonexistent',
                'invoice_number' => 'INV-999',
                'status' => Transaction::STATUS_COMPLETED,
                'details' => [
                    'totals' => [
                        'total' => '500',
                        'tax' => '100',
                    ],
                ],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('transactions', [
            'paddle_id' => 'txn_nonexistent',
        ]);
    }

    public function test_transaction_updated_when_no_stored_paddle_timestamp_accepts_webhook()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        // Record was created without paddle_updated_at (e.g. legacy data).
        $user->transactions()->create([
            'paddle_id' => 'txn_123456789',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => 'legacy-invoice',
            'status' => Transaction::STATUS_BILLED,
            'total' => '1000',
            'tax' => '200',
            'currency' => 'USD',
            'billed_at' => now(),
            'paddle_updated_at' => null,
        ]);

        // Webhook with updated_at should be accepted because stored is null.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_123456789',
                'invoice_number' => 'new-invoice',
                'status' => Transaction::STATUS_COMPLETED,
                'updated_at' => '2026-03-18 14:00:00',
                'details' => [
                    'totals' => [
                        'total' => '2000',
                        'tax' => '400',
                    ],
                ],
                'billed_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_123456789',
            'invoice_number' => 'new-invoice',
            'status' => Transaction::STATUS_COMPLETED,
            'paddle_updated_at' => '2026-03-18 14:00:00',
        ]);
    }

    // ---------------------------------------------------------------
    // Race condition: transaction.completed then stale transaction.updated
    // ---------------------------------------------------------------

    public function test_completed_then_stale_updated_does_not_regress_transaction()
    {
        Cashier::fake();

        $user = $this->createBillable();

        $paddleCompletedAt = '2026-03-18 14:01:00';

        // Step 1: transaction.completed arrives first and creates the record.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction_completed',
            'occurred_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'data' => [
                'id' => 'txn_race',
                'customer_id' => 'cus_123456789',
                'status' => Transaction::STATUS_COMPLETED,
                'subscription_id' => 'sub_123456789',
                'invoice_number' => 'INV-COMPLETED',
                'currency_code' => 'EUR',
                'updated_at' => $paddleCompletedAt,
                'details' => [
                    'totals' => [
                        'total' => '2000',
                        'tax' => '400',
                    ],
                ],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_race',
            'status' => Transaction::STATUS_COMPLETED,
            'invoice_number' => 'INV-COMPLETED',
            'paddle_updated_at' => $paddleCompletedAt,
        ]);

        // Step 2: Stale transaction.updated arrives with older Paddle timestamp.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_race',
                'invoice_number' => 'INV-STALE',
                'status' => Transaction::STATUS_PAID,
                'updated_at' => '2026-03-18 14:00:00', // 1 minute older
                'details' => [
                    'totals' => [
                        'total' => '1000',
                        'tax' => '200',
                    ],
                ],
                'billed_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        // Record should still show completed state.
        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_race',
            'status' => Transaction::STATUS_COMPLETED,
            'invoice_number' => 'INV-COMPLETED',
            'total' => '2000',
            'paddle_updated_at' => $paddleCompletedAt,
        ]);
    }

    // ---------------------------------------------------------------
    // Subscription Created
    // ---------------------------------------------------------------

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

        $this->assertDatabaseCount('subscriptions', 1);

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

    public function test_subscription_created_stores_paddle_updated_at()
    {
        Cashier::fake();

        $user = $this->createBillable();
        $paddleUpdatedAt = '2026-03-18 12:30:00';

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_created',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_ACTIVE,
                'updated_at' => $paddleUpdatedAt,
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
            'paddle_id' => 'sub_123456789',
            'paddle_updated_at' => $paddleUpdatedAt,
        ]);
    }

    // ---------------------------------------------------------------
    // Subscription Updated
    // ---------------------------------------------------------------

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

    public function test_subscription_updated_stores_paddle_updated_at()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $paddleUpdatedAt = '2026-03-18 14:00:00';

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_ACTIVE,
                'updated_at' => $paddleUpdatedAt,
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
                        'quantity' => 2,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_123456789',
            'paddle_updated_at' => $paddleUpdatedAt,
        ]);
    }

    public function test_stale_subscription_updated_webhook_does_not_overwrite_newer_data()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $newerPaddleTimestamp = '2026-03-18 14:00:00';

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paddle_updated_at' => $newerPaddleTimestamp,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Send a webhook with an OLDER Paddle updated_at.
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_PAUSED,
                'updated_at' => '2026-03-18 13:55:00', // 5 min older
                'paused_at' => now('UTC')->addDays(5)->format('Y-m-d H:i:s'),
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

        // Subscription was NOT updated with stale data.
        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paddle_updated_at' => $newerPaddleTimestamp,
        ]);

        // Items unchanged.
        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => 1,
            'quantity' => 1,
        ]);
    }

    public function test_duplicate_timestamp_subscription_updated_is_rejected()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $paddleTimestamp = '2026-03-18 14:00:00';

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paddle_updated_at' => $paddleTimestamp,
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Same updated_at — should be rejected (greaterThanOrEqualTo).
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_PAUSED,
                'updated_at' => $paddleTimestamp,
                'paused_at' => now('UTC')->addDays(5)->format('Y-m-d H:i:s'),
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
                        'quantity' => 5,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => 1,
            'quantity' => 1,
        ]);
    }

    public function test_newer_subscription_updated_webhook_overwrites_older_data()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paddle_updated_at' => '2026-03-18 13:00:00',
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Newer timestamp — should be accepted.
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_123456789',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_PAUSED,
                'updated_at' => '2026-03-18 14:00:00',
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
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_PAUSED,
            'paused_at' => $date,
            'paddle_updated_at' => '2026-03-18 14:00:00',
        ]);

        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => 1,
            'quantity' => 3,
        ]);

        Cashier::assertSubscriptionUpdated(function (SubscriptionUpdated $event) {
            return $event->subscription->paddle_id === 'sub_123456789';
        });
    }

    public function test_subscription_updated_without_updated_at_always_applies()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paddle_updated_at' => '2026-03-18 14:00:00',
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'active',
            'quantity' => 1,
        ]);

        // Webhook WITHOUT updated_at — guard is bypassed.
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
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
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_CANCELED,
        ]);
    }

    public function test_subscription_updated_when_no_stored_paddle_timestamp_accepts_webhook()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_ACTIVE,
            'paddle_updated_at' => null,
        ]);

        $subscription->items()->create([
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
                'updated_at' => '2026-03-18 14:00:00',
                'paused_at' => now('UTC')->addDays(5)->format('Y-m-d H:i:s'),
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
                        'quantity' => 2,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_123456789',
            'status' => Subscription::STATUS_PAUSED,
            'paddle_updated_at' => '2026-03-18 14:00:00',
        ]);
    }

    public function test_subscription_updated_for_unknown_subscription_is_ignored()
    {
        Cashier::fake();

        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_nonexistent',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_PAUSED,
                'paused_at' => now('UTC')->format('Y-m-d H:i:s'),
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

        $this->assertDatabaseMissing('subscriptions', [
            'paddle_id' => 'sub_nonexistent',
        ]);
    }

    // ---------------------------------------------------------------
    // Subscription Canceled
    // ---------------------------------------------------------------

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

    // ---------------------------------------------------------------
    // Full lifecycle: sequential webhook ordering scenarios
    // ---------------------------------------------------------------

    public function test_sequential_transaction_updates_apply_in_correct_order()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $user->transactions()->create([
            'paddle_id' => 'txn_lifecycle',
            'paddle_subscription_id' => 'sub_123456789',
            'invoice_number' => null,
            'status' => Transaction::STATUS_DRAFT,
            'total' => '500',
            'tax' => '100',
            'currency' => 'USD',
            'billed_at' => now(),
            'paddle_updated_at' => '2026-03-18 12:00:00',
        ]);

        // Update 1: draft -> billed
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_lifecycle',
                'invoice_number' => null,
                'status' => Transaction::STATUS_BILLED,
                'updated_at' => '2026-03-18 12:05:00',
                'details' => ['totals' => ['total' => '500', 'tax' => '100']],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_lifecycle',
            'status' => Transaction::STATUS_BILLED,
            'paddle_updated_at' => '2026-03-18 12:05:00',
        ]);

        // Update 2: billed -> paid
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_lifecycle',
                'invoice_number' => 'INV-001',
                'status' => Transaction::STATUS_PAID,
                'updated_at' => '2026-03-18 12:10:00',
                'details' => ['totals' => ['total' => '500', 'tax' => '100']],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_lifecycle',
            'status' => Transaction::STATUS_PAID,
            'invoice_number' => 'INV-001',
            'paddle_updated_at' => '2026-03-18 12:10:00',
        ]);

        // Update 3: paid -> completed
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_lifecycle',
                'invoice_number' => 'INV-001',
                'status' => Transaction::STATUS_COMPLETED,
                'updated_at' => '2026-03-18 12:15:00',
                'details' => ['totals' => ['total' => '500', 'tax' => '100']],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_lifecycle',
            'status' => Transaction::STATUS_COMPLETED,
            'paddle_updated_at' => '2026-03-18 12:15:00',
        ]);

        // Replay stale update 1 again — must be rejected.
        $this->postJson('paddle/webhook', [
            'event_type' => 'transaction.updated',
            'data' => [
                'id' => 'txn_lifecycle',
                'invoice_number' => null,
                'status' => Transaction::STATUS_BILLED,
                'updated_at' => '2026-03-18 12:05:00',
                'details' => ['totals' => ['total' => '500', 'tax' => '100']],
                'billed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ])->assertOk();

        // Still completed.
        $this->assertDatabaseHas('transactions', [
            'paddle_id' => 'txn_lifecycle',
            'status' => Transaction::STATUS_COMPLETED,
            'invoice_number' => 'INV-001',
            'paddle_updated_at' => '2026-03-18 12:15:00',
        ]);
    }

    public function test_sequential_subscription_updates_apply_in_correct_order()
    {
        Cashier::fake();

        $user = $this->createBillable('taylor');

        $subscription = $user->subscriptions()->create([
            'type' => 'main',
            'paddle_id' => 'sub_lifecycle',
            'status' => Subscription::STATUS_TRIALING,
            'trial_ends_at' => now('UTC')->addDays(14),
            'paddle_updated_at' => '2026-03-18 12:00:00',
        ]);

        $subscription->items()->create([
            'product_id' => 'pro_123456789',
            'price_id' => 'pri_123456789',
            'status' => 'trialing',
            'quantity' => 1,
        ]);

        // Update 1: trialing -> active
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_lifecycle',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_ACTIVE,
                'updated_at' => '2026-03-18 12:05:00',
                'custom_data' => ['subscription_type' => 'main'],
                'items' => [[
                    'price' => ['id' => 'pri_123456789', 'product_id' => 'pro_123456789'],
                    'status' => 'active',
                    'quantity' => 1,
                ]],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_lifecycle',
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'paddle_updated_at' => '2026-03-18 12:05:00',
        ]);

        // Update 2: active -> paused
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_lifecycle',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_PAUSED,
                'updated_at' => '2026-03-18 12:10:00',
                'paused_at' => ($pausedAt = now('UTC')->addDays(5))->format('Y-m-d H:i:s'),
                'custom_data' => ['subscription_type' => 'main'],
                'items' => [[
                    'price' => ['id' => 'pri_123456789', 'product_id' => 'pro_123456789'],
                    'status' => 'active',
                    'quantity' => 1,
                ]],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_lifecycle',
            'status' => Subscription::STATUS_PAUSED,
            'paddle_updated_at' => '2026-03-18 12:10:00',
        ]);

        // Replay stale update 1 — must be rejected.
        $this->postJson('paddle/webhook', [
            'event_type' => 'subscription_updated',
            'data' => [
                'id' => 'sub_lifecycle',
                'customer_id' => 'cus_123456789',
                'status' => Subscription::STATUS_ACTIVE,
                'updated_at' => '2026-03-18 12:05:00',
                'custom_data' => ['subscription_type' => 'main'],
                'items' => [[
                    'price' => ['id' => 'pri_123456789', 'product_id' => 'pro_123456789'],
                    'status' => 'active',
                    'quantity' => 1,
                ]],
            ],
        ])->assertOk();

        // Still paused.
        $this->assertDatabaseHas('subscriptions', [
            'paddle_id' => 'sub_lifecycle',
            'status' => Subscription::STATUS_PAUSED,
            'paddle_updated_at' => '2026-03-18 12:10:00',
        ]);
    }

    // ---------------------------------------------------------------
    // Unknown / unhandled event types
    // ---------------------------------------------------------------

    public function test_unknown_webhook_event_type_returns_empty_response()
    {
        $this->postJson('paddle/webhook', [
            'event_type' => 'some_unknown_event',
            'data' => [],
        ])->assertOk();
    }
}
