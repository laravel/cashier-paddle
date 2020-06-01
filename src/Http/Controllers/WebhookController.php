<?php

namespace Laravel\Paddle\Http\Controllers;

use Carbon\Carbon;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
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
     * Handle subscription created.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionCreated(array $payload)
    {
        $passthrough = json_decode($payload['passthrough'], true);

        $model = config('cashier.model');

        if (! $user = (new $model)->find($passthrough['customer_id'])) {
            return;
        }

        $user->forceFill([
            'paddle_id' => $payload['user_id'],
            'paddle_email' => $payload['email'],
        ])->save();

        $trialEndsAt = $payload['status'] === Subscription::STATUS_TRIALING
            ? Carbon::createFromFormat('Y-m-d', $payload['next_bill_date'], 'UTC')->startOfDay()
            : null;

        $subscription = $user->subscriptions()->create([
            'name' => $passthrough['subscription_name'],
            'paddle_id' => $payload['subscription_id'],
            'paddle_plan' => $payload['subscription_plan_id'],
            'paddle_status' => $payload['status'],
            'quantity' => $payload['quantity'],
            'trial_ends_at' => $trialEndsAt,
        ]);

        $subscription->syncPaymentInformation()->save();
    }

    /**
     * Handle subscription updated.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionUpdated(array $payload)
    {
        if (! $user = Cashier::findBillable($payload['user_id'])) {
            return;
        }

        if ($subscription = $user->subscriptions()->where('paddle_id', $payload['subscription_id'])->first()) {
            // Plan...
            if (isset($payload['subscription_plan_id'])) {
                $subscription->paddle_plan = $payload['subscription_plan_id'];
            }

            // Status...
            if (isset($payload['status'])) {
                $subscription->paddle_status = $payload['status'];
            }

            // Quantity...
            if (isset($payload['quantity'])) {
                $subscription->quantity = $payload['quantity'];
            }

            // Paused...
            if (isset($payload['paused_from'])) {
                $subscription->paused_from = Carbon::createFromFormat('Y-m-d H:i:s', $payload['paused_from'], 'UTC');
            } else {
                $subscription->paused_from = null;
            }

            $subscription->save();
        }
    }

    /**
     * Handle subscription cancelled.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionCancelled(array $payload)
    {
        if (! $user = Cashier::findBillable($payload['user_id'])) {
            return;
        }

        if ($subscription = $user->subscriptions()->where('paddle_id', $payload['subscription_id'])->first()) {
            // Cancellation date...
            if (isset($payload['cancellation_effective_date'])) {
                if ($payload['cancellation_effective_date']) {
                    $subscription->ends_at = $subscription->onTrial()
                        ? $subscription->trial_ends_at
                        : Carbon::createFromFormat('Y-m-d', $payload['cancellation_effective_date'], 'UTC')->startOfDay();
                } else {
                    $subscription->ends_at = null;
                }
            }

            // Status...
            if (isset($payload['status'])) {
                $subscription->paddle_status = $payload['status'];
            }

            $subscription->save();
        }
    }
}
