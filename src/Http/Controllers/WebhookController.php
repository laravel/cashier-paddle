<?php

namespace Laravel\Paddle\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laravel\Paddle\Customer;
use Laravel\Paddle\Events\PaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionCancelled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaymentFailed;
use Laravel\Paddle\Events\SubscriptionPaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;
use Laravel\Paddle\Receipt;
use Laravel\Paddle\Subscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (config('cashier.public_key')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    /**
     * Handle a Paddle webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        if (! isset($payload['alert_name'])) {
            return new Response();
        }

        $method = 'handle'.Str::studly($payload['alert_name']);

        WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return new Response('Webhook Handled');
        }

        return new Response();
    }

    /**
     * Handle one-time payment succeeded.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handlePaymentSucceeded(array $payload)
    {
        if ($this->receiptExists($payload['order_id'])) {
            return;
        }

        $customer = $this->findOrCreateCustomer($payload['passthrough']);

        $receipt = $customer->receipts()->create([
            'checkout_id' => $payload['checkout_id'],
            'order_id' => $payload['order_id'],
            'amount' => $payload['sale_gross'],
            'tax' => $payload['payment_tax'],
            'currency' => $payload['currency'],
            'quantity' => (int) $payload['quantity'],
            'receipt_url' => $payload['receipt_url'],
            'paid_at' => Carbon::createFromFormat('Y-m-d H:i:s', $payload['event_time'], 'UTC'),
        ]);

        PaymentSucceeded::dispatch($customer, $receipt, $payload);
    }

    /**
     * Handle subscription payment succeeded.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionPaymentSucceeded(array $payload)
    {
        if ($this->receiptExists($payload['order_id'])) {
            return;
        }

        if ($subscription = $this->findSubscription($payload['subscription_id'])) {
            $billable = $subscription->billable;
        } else {
            $billable = $this->findOrCreateCustomer($payload['passthrough']);
        }

        $receipt = $billable->receipts()->create([
            'paddle_subscription_id' => $payload['subscription_id'],
            'checkout_id' => $payload['checkout_id'],
            'order_id' => $payload['order_id'],
            'amount' => $payload['sale_gross'],
            'tax' => $payload['payment_tax'],
            'currency' => $payload['currency'],
            'quantity' => (int) $payload['quantity'],
            'receipt_url' => $payload['receipt_url'],
            'paid_at' => Carbon::createFromFormat('Y-m-d H:i:s', $payload['event_time'], 'UTC'),
        ]);

        SubscriptionPaymentSucceeded::dispatch($billable, $receipt, $payload);
    }

    /**
     * Handle subscription payment failed.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionPaymentFailed(array $payload)
    {
        if ($subscription = $this->findSubscription($payload['subscription_id'])) {
            SubscriptionPaymentFailed::dispatch($subscription->billable, $payload);
        }
    }

    /**
     * Handle subscription created.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionCreated(array $payload)
    {
        $passthrough = json_decode($payload['passthrough'], true);

        $trialEndsAt = $payload['status'] === Subscription::STATUS_TRIALING
            ? Carbon::createFromFormat('Y-m-d', $payload['next_bill_date'], 'UTC')->startOfDay()
            : null;

        $customer = $this->findOrCreateCustomer($payload['passthrough']);

        $subscription = $customer->subscriptions()->create([
            'name' => $passthrough['subscription_name'],
            'paddle_id' => $payload['subscription_id'],
            'paddle_plan' => $payload['subscription_plan_id'],
            'paddle_status' => $payload['status'],
            'quantity' => $payload['quantity'],
            'trial_ends_at' => $trialEndsAt,
        ]);

        SubscriptionCreated::dispatch($customer, $subscription, $payload);
    }

    /**
     * Handle subscription updated.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionUpdated(array $payload)
    {
        if (! $subscription = $this->findSubscription($payload['subscription_id'])) {
            return;
        }

        // Plan...
        if (isset($payload['subscription_plan_id'])) {
            $subscription->paddle_plan = $payload['subscription_plan_id'];
        }

        // Status...
        if (isset($payload['status'])) {
            $subscription->paddle_status = $payload['status'];
        }

        // Quantity...
        if (isset($payload['new_quantity'])) {
            $subscription->quantity = $payload['new_quantity'];
        }

        // Paused...
        if (isset($payload['paused_from'])) {
            $subscription->paused_from = Carbon::createFromFormat('Y-m-d H:i:s', $payload['paused_from'], 'UTC');
        } else {
            $subscription->paused_from = null;
        }

        $subscription->save();

        SubscriptionUpdated::dispatch($subscription, $payload);
    }

    /**
     * Handle subscription cancelled.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionCancelled(array $payload)
    {
        if (! $subscription = $this->findSubscription($payload['subscription_id'])) {
            return;
        }

        // Cancellation date...
        $subscription->ends_at = $subscription->onTrial()
            ? $subscription->trial_ends_at
            : Carbon::createFromFormat('Y-m-d', $payload['cancellation_effective_date'], 'UTC')->startOfDay();

        // Status...
        if (isset($payload['status'])) {
            $subscription->paddle_status = $payload['status'];
        }

        $subscription->paused_from = null;

        $subscription->save();

        SubscriptionCancelled::dispatch($subscription, $payload);
    }

    /**
     * Find or create a customer based on the passthrough values and return the billable model.
     *
     * @param  string  $passthrough
     * @return \Laravel\Paddle\Billable
     */
    protected function findOrCreateCustomer(string $passthrough)
    {
        $passthrough = json_decode($passthrough, true);

        return Customer::firstOrCreate([
            'billable_id' => $passthrough['billable_id'],
            'billable_type' => $passthrough['billable_type'],
        ])->billable;
    }

    /**
     * Find the first subscription matching a Paddle subscription id.
     *
     * @param  string  $subscriptionId
     * @return \Laravel\Paddle\Subscription|null
     */
    protected function findSubscription(string $subscriptionId)
    {
        return Subscription::firstWhere('paddle_id', $subscriptionId);
    }

    /**
     * Determine if a receipt with a given Order ID already exists.
     *
     * @param  string  $orderId
     * @return bool
     */
    protected function receiptExists(string $orderId)
    {
        return Receipt::where('order_id', $orderId)->count() > 0;
    }
}
