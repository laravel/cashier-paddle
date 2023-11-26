<?php

namespace Laravel\Paddle\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\CustomerUpdated;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionCreated;
use Laravel\Paddle\Events\SubscriptionPaused;
use Laravel\Paddle\Events\SubscriptionUpdated;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Events\TransactionUpdated;
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
     * Handle customer updated.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleCustomerUpdated(array $payload)
    {
        $data = $payload['data'];

        if (! $customer = $this->findCustomer($data['id'])) {
            return;
        }

        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        CustomerUpdated::dispatch($customer->billable, $customer, $payload);
    }

    /**
     * Handle transaction completed.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleTransactionCompleted(array $payload)
    {
        $data = $payload['data'];

        if ($this->transactionExists($data['id'])) {
            return;
        }

        if (! $billable = $this->findBillable($data['customer_id'])) {
            return;
        }

        $transaction = $billable->transactions()->create([
            'paddle_id' => $data['id'],
            'paddle_subscription_id' => $data['subscription_id'],
            'invoice_number' => $data['invoice_number'],
            'status' => $data['status'],
            'total' => $data['details']['totals']['total'],
            'tax' => $data['details']['totals']['tax'],
            'currency' => $data['currency_code'],
            'billed_at' => Carbon::parse($data['billed_at'], 'UTC'),
        ]);

        TransactionCompleted::dispatch($billable, $transaction, $payload);
    }

    /**
     * Handle transaction updated.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleTransactionUpdated(array $payload)
    {
        $data = $payload['data'];

        if (! $transaction = $this->findTransaction($data['id'])) {
            return;
        }

        $transaction->update([
            'invoice_number' => $data['invoice_number'],
            'status' => $data['status'],
            'total' => $data['details']['totals']['total'],
            'tax' => $data['details']['totals']['tax'],
            'billed_at' => Carbon::parse($data['billed_at'], 'UTC'),
        ]);

        TransactionUpdated::dispatch($transaction->billable, $transaction, $payload);
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

        if (! $billable = $this->findBillable($data['customer_id'])) {
            return;
        }

        $subscription = $billable->subscriptions()->create([
            'type' => $data['custom_data']['subscription_type'] ?? Subscription::DEFAULT_TYPE,
            'paddle_id' => $data['id'],
            'status' => $data['status'],
            'trial_ends_at' => $data['status'] === Subscription::STATUS_TRIALING
                ? Carbon::parse($data['next_billed_at'], 'UTC')
                : null,
        ]);

        foreach ($data['items'] as $item) {
            $subscription->items()->create([
                'product_id' => $item['price']['product_id'],
                'price_id' => $item['price']['id'],
                'status' => $item['status'],
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }

        SubscriptionCreated::dispatch($billable, $subscription, $payload);
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

        if ($data['status'] === Subscription::STATUS_TRIALING) {
            $subscription->trial_ends_at = Carbon::parse($data['next_billed_at'], 'UTC');
        } else {
            $subscription->trial_ends_at = null;
        }

        if (isset($data['paused_at'])) {
            $subscription->paused_at = Carbon::parse($data['paused_at'], 'UTC');
        } elseif (isset($data['scheduled_change']) && $data['scheduled_change']['action'] === 'pause') {
            $subscription->paused_at = Carbon::parse($data['scheduled_change']['effective_at'], 'UTC');
        } else {
            $subscription->paused_at = null;
        }

        if (isset($data['canceled_at'])) {
            $subscription->ends_at = Carbon::parse($data['canceled_at'], 'UTC');
        } elseif (isset($data['scheduled_change']) && $data['scheduled_change']['action'] === 'cancel') {
            $subscription->ends_at = Carbon::parse($data['scheduled_change']['effective_at'], 'UTC');
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

        $subscription->status = $data['status'];

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

        $subscription->status = $data['status'];

        $subscription->ends_at = Carbon::parse($data['canceled_at'], 'UTC');

        $subscription->paused_at = null;

        $subscription->save();

        SubscriptionCanceled::dispatch($subscription, $payload);
    }

    /**
     * Get the customer instance by its Paddle customer ID.
     *
     * @param  string  $customerId
     * @return \Laravel\Paddle\Billable|null
     */
    protected function findBillable($customerId)
    {
        return Cashier::findBillable($customerId);
    }

    /**
     * Find the first customer matching a Paddle customer ID.
     *
     * @param  string  $customerId
     * @return \Laravel\Paddle\Customer|null
     */
    protected function findCustomer(string $customerId)
    {
        return Cashier::$customerModel::firstWhere('paddle_id', $customerId);
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
     * Find the first transaction matching a Paddle transaction ID.
     *
     * @param  string  $transactionId
     * @return \Laravel\Paddle\Transaction|null
     */
    protected function findTransaction(string $transactionId)
    {
        return Cashier::$transactionModel::firstWhere('paddle_id', $transactionId);
    }

    /**
     * Determine if a transaction with a given ID already exists.
     *
     * @param  string  $transactionId
     * @return bool
     */
    protected function transactionExists(string $transactionId)
    {
        return Cashier::$transactionModel::where('paddle_id', $transactionId)->count() > 0;
    }
}
