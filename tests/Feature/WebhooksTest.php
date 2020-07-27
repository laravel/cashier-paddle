<?php

namespace Tests\Feature;

use Laravel\Paddle\Subscription;

class WebhooksTest extends FeatureTestCase
{
    public function test_gracefully_handle_webhook_without_alert_name()
    {
        $this->postJson('paddle/webhook', [
            'event_time' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertOk();
    }

    public function test_it_can_handle_a_payment_succeeded_event()
    {
        if (! isset($_SERVER['PADDLE_TEST_CHECKOUT'])) {
            $this->markTestSkipped('Checkout identifier not configured');
        }

        $user = $this->createUser();

        $this->postJson('paddle/webhook', [
            'alert_name' => 'payment_succeeded',
            'event_time' => $paidAt = now()->addDay()->format('Y-m-d H:i:s'),
            'checkout_id' => $_SERVER['PADDLE_TEST_CHECKOUT'],
            'order_id' => 'foo',
            'email' => $user->paddleEmail(),
            'sale_gross' => '12.55',
            'payment_tax' => '4.34',
            'currency' => 'EUR',
            'quantity' => 1,
            'receipt_url' => 'https://example.com/receipt.pdf',
            'passthrough' => json_encode([
                'billable_id' => $user->id,
                'billable_type' => $user->getMorphClass(),
            ]),
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
        ]);

        $this->assertDatabaseHas('receipts', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_subscription_id' => null,
            'paid_at' => $paidAt,
            'checkout_id' => $_SERVER['PADDLE_TEST_CHECKOUT'],
            'order_id' => 'foo',
            'amount' => '12.55',
            'tax' => '4.34',
            'currency' => 'EUR',
            'quantity' => 1,
            'receipt_url' => 'https://example.com/receipt.pdf',
        ]);
    }

    /** @test */
    public function test_it_can_handle_a_payment_succeeded_event_when_billable_already_exists()
    {
        if (! isset($_SERVER['PADDLE_TEST_CHECKOUT'])) {
            $this->markTestSkipped('Checkout identifier not configured');
        }

        $user = $this->createBillable('taylor', [
            'trial_ends_at' => now('UTC')->addDays(5),
        ]);

        $this->postJson('paddle/webhook', [
            'alert_name' => 'payment_succeeded',
            'event_time' => $paidAt = now()->addDay()->format('Y-m-d H:i:s'),
            'checkout_id' => $_SERVER['PADDLE_TEST_CHECKOUT'],
            'order_id' => 'foo',
            'email' => $user->paddleEmail(),
            'sale_gross' => '12.55',
            'payment_tax' => '4.34',
            'currency' => 'EUR',
            'quantity' => 1,
            'receipt_url' => 'https://example.com/receipt.pdf',
            'passthrough' => json_encode([
                'billable_id' => $user->id,
                'billable_type' => $user->getMorphClass(),
            ]),
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
        ]);

        $this->assertDatabaseHas('receipts', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_subscription_id' => null,
            'paid_at' => $paidAt,
            'checkout_id' => $_SERVER['PADDLE_TEST_CHECKOUT'],
            'order_id' => 'foo',
            'amount' => '12.55',
            'tax' => '4.34',
            'currency' => 'EUR',
            'quantity' => 1,
            'receipt_url' => 'https://example.com/receipt.pdf',
        ]);
    }

    public function test_it_can_handle_a_subscription_payment_succeeded_event()
    {
        if (! isset($_SERVER['PADDLE_TEST_CHECKOUT'])) {
            $this->markTestSkipped('Checkout identifier not configured');
        }

        $user = $this->createBillable();

        $subscription = $user->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $this->postJson('paddle/webhook', [
            'alert_name' => 'subscription_payment_succeeded',
            'event_time' => $paidAt = now()->addDay()->format('Y-m-d H:i:s'),
            'subscription_id' => $subscription->paddle_id,
            'checkout_id' => $_SERVER['PADDLE_TEST_CHECKOUT'],
            'order_id' => 'foo',
            'email' => $user->paddleEmail(),
            'sale_gross' => '12.55',
            'payment_tax' => '4.34',
            'currency' => 'EUR',
            'quantity' => 1,
            'receipt_url' => 'https://example.com/receipt.pdf',
            'passthrough' => json_encode([
                'billable_id' => $user->id,
                'billable_type' => $user->getMorphClass(),
            ]),
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
        ]);

        $this->assertDatabaseHas('receipts', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_subscription_id' => $subscription->paddle_id,
            'paid_at' => $paidAt,
            'checkout_id' => $_SERVER['PADDLE_TEST_CHECKOUT'],
            'order_id' => 'foo',
            'amount' => '12.55',
            'tax' => '4.34',
            'currency' => 'EUR',
            'quantity' => 1,
            'receipt_url' => 'https://example.com/receipt.pdf',
        ]);
    }

    public function test_it_can_handle_a_subscription_created_event()
    {
        $user = $this->createUser();

        $this->postJson('paddle/webhook', [
            'alert_name' => 'subscription_created',
            'user_id' => 'foo',
            'email' => $user->paddleEmail(),
            'passthrough' => json_encode([
                'billable_id' => $user->id,
                'billable_type' => $user->getMorphClass(),
                'subscription_name' => 'main',
            ]),
            'quantity' => 1,
            'status' => Subscription::STATUS_ACTIVE,
            'subscription_id' => 'bar',
            'subscription_plan_id' => 1234,
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'name' => 'main',
            'paddle_id' => 'bar',
            'paddle_plan' => 1234,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
            'trial_ends_at' => null,
        ]);
    }

    /** @test */
    public function test_it_can_handle_a_subscription_created_event_if_billable_already_exists()
    {
        $user = $this->createUser();
        $user->customer()->create([
            'trial_ends_at' => now('UTC')->addDays(5),
        ]);

        $this->postJson('paddle/webhook', [
            'alert_name' => 'subscription_created',
            'user_id' => 'foo',
            'email' => $user->paddleEmail(),
            'passthrough' => json_encode([
                'billable_id' => $user->id,
                'billable_type' => $user->getMorphClass(),
                'subscription_name' => 'main',
            ]),
            'quantity' => 1,
            'status' => Subscription::STATUS_ACTIVE,
            'subscription_id' => 'bar',
            'subscription_plan_id' => 1234,
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'name' => 'main',
            'paddle_id' => 'bar',
            'paddle_plan' => 1234,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
            'trial_ends_at' => null,
        ]);
    }

    public function test_it_can_handle_a_subscription_updated_event()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $this->postJson('paddle/webhook', [
            'alert_name' => 'subscription_updated',
            'new_quantity' => 3,
            'status' => Subscription::STATUS_PAUSED,
            'paused_from' => ($date = now('UTC')->addDays(5))->format('Y-m-d H:i:s'),
            'subscription_id' => 244,
            'subscription_plan_id' => 1234,
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'billable_id' => $billable->id,
            'billable_type' => $billable->getMorphClass(),
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 1234,
            'paddle_status' => Subscription::STATUS_PAUSED,
            'quantity' => 3,
            'paused_from' => $date,
        ]);
    }

    public function test_it_can_handle_a_subscription_cancelled_event()
    {
        $billable = $this->createBillable('taylor');

        $subscription = $billable->subscriptions()->create([
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_plan' => 2323,
            'paddle_status' => Subscription::STATUS_ACTIVE,
            'quantity' => 1,
        ]);

        $this->postJson('paddle/webhook', [
            'alert_name' => 'subscription_cancelled',
            'status' => Subscription::STATUS_DELETED,
            'cancellation_effective_date' => ($date = now('UTC')->addDays(5)->startOfDay())->format('Y-m-d'),
            'subscription_id' => 244,
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'billable_id' => $billable->id,
            'billable_type' => $billable->getMorphClass(),
            'name' => 'main',
            'paddle_id' => 244,
            'paddle_status' => Subscription::STATUS_DELETED,
            'ends_at' => $date,
        ]);
    }
}
