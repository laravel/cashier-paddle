<?php

namespace Laravel\Paddle\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\PaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaused;
use Laravel\Paddle\Events\SubscriptionPaymentFailed;
use Laravel\Paddle\Events\SubscriptionPaymentSucceeded;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;
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
        if (config('cashier.webhook_secret')) {
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

        $method = 'handle'.Str::studly(Str::replace('.', ' ', $payload['event_type']));

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
        $data = $payload['data'];

        if ($this->subscriptionExists($data['id'])) {
            return;
        }

        if (! $customer = $this->findCustomer($data['customer_id'])) {
            return;
        }

        $subscription = $customer->subscriptions()->create([
            'type' => $data['custom_data']['subscription_type'] ?? Subscription::DEFAULT_TYPE,
            'paddle_id' => $data['id'],
            'status' => $data['status'],
            'trial_ends_at' => null, // @todo
        ]);

        foreach ($data['items'] as $item) {
            $subscription->items()->create([
                'product_id' => $item['price']['product_id'],
                'price_id' => $item['price']['id'],
                'status' => $item['status'],
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }

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
        $data = $payload['data'];

        if (! $subscription = $this->findSubscription($data['id'])) {
            return;
        }

        $subscription->status = $data['status'];

        if (isset($data['paused_at'])) {
            $subscription->paused_at = Carbon::parse($data['paused_at'], 'UTC');
        } else {
            $subscription->paused_at = null;
        }

        if (isset($data['canceled_at'])) {
            $subscription->ends_at = Carbon::parse($data['canceled_at'], 'UTC');
        } else {
            $subscription->ends_at = null;
        }

        $subscription->save();

        $prices = [];

        foreach ($data['items'] as $item) {
            $prices[] = $item['price']['id'];

            $subscription->items()->updateOrCreate([
                'price_id' => $item['price']['id'],
            ], [
                'product_id' => $item['price']['product_id'],
                'status' => $item['status'],
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }

        // Delete items that aren't attached to the subscription anymore...
        $subscription->items()->whereNotIn('price_id', $prices)->delete();

        SubscriptionUpdated::dispatch($subscription, $payload);
    }

    /**
     * Handle subscription paused.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionPaused(array $payload)
    {
        $data = $payload['data'];

        if (! $subscription = $this->findSubscription($data['id'])) {
            return;
        }

        // Status...
        $subscription->status = $data['status'];

        // Cancellation date...
        $subscription->paused_at = Carbon::parse($data['paused_at'], 'UTC');

        $subscription->ends_at = null;

        $subscription->save();

        SubscriptionPaused::dispatch($subscription, $payload);
    }

    /**
     * Handle subscription canceled.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionCanceled(array $payload)
    {
        $data = $payload['data'];

        if (! $subscription = $this->findSubscription($data['id'])) {
            return;
        }

        // Status...
        $subscription->status = $data['status'];

        // Cancellation date...
        $subscription->ends_at = Carbon::parse($data['canceled_at'], 'UTC');

        $subscription->paused_at = null;

        $subscription->save();

        SubscriptionCanceled::dispatch($subscription, $payload);
    }

    /**
     * Get the customer instance by its Paddle customer ID.
     *
     * @param  string  $paddleId
     * @return \Laravel\Paddle\Billable|null
     */
    protected function findCustomer($customerId)
    {
        return Cashier::findBillable($customerId);
    }

    /**
     * Find the first subscription matching a Paddle subscription ID.
     *
     * @param  string  $subscriptionId
     * @return \Laravel\Paddle\Subscription|null
     */
    protected function findSubscription(string $subscriptionId)
    {
        return Cashier::$subscriptionModel::firstWhere('paddle_id', $subscriptionId);
    }

    /**
     * Determine if a subscription with a given Paddle ID already exists.
     *
     * @param  string  $subscriptionId
     * @return bool
     */
    protected function subscriptionExists(string $subscriptionId)
    {
        return Cashier::$subscriptionModel::where('paddle_id', $subscriptionId)->exists();
    }

    /**
     * Determine if a receipt with a given Order ID already exists.
     *
     * @param  string  $orderId
     * @return bool
     */
    protected function receiptExists(string $orderId)
    {
        return Cashier::$receiptModel::where('order_id', $orderId)->count() > 0;
    }
}
