<?php

namespace Laravel\Paddle\Http\Controllers;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle'.Str::studly($payload['object']['alert_name']);

        WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            $this->{$method}($payload);

            WebhookHandled::dispatch($payload['object']);

            return new Response('Webhook Handled');
        }

        return new Response();
    }

    /**
     * Handle subscription updated.
     *
     * @param  array  $object
     * @return void
     */
    protected function handleSubscriptionUpdated(array $object)
    {
        if (! $user = Cashier::findBillable($object['user_id'])) {
            return;
        }

        if (! $subscription = $user->subscriptions()->where('paddle_id', $object['subscription_id'])->first()) {
            // Quantity...
            if (isset($object['quantity'])) {
                $subscription->quantity = $object['quantity'];
            }

            // Plan...
            if (isset($object['subscription_plan_id'])) {
                $subscription->paddle_plan = $object['subscription_plan_id'];
            }

            // Status...
            if (isset($object['status'])) {
                $subscription->paddle_status = $object['status'];
            }

            $subscription->save();
        }
    }

    /**
     * Handle subscription cancelled.
     *
     * @param  array  $object
     * @return void
     */
    protected function handleSubscriptionCancelled(array $object): void
    {
        if (! $user = Cashier::findBillable($object['user_id'])) {
            return;
        }

        if (! $subscription = $user->subscriptions()->where('paddle_id', $object['subscription_id'])->first()) {
            // Cancellation date...
            if (isset($object['cancellation_effective_date'])) {
                if ($object['cancellation_effective_date']) {
                    $subscription->ends_at = $subscription->onTrial()
                        ? $subscription->trial_ends_at
                        : Carbon::createFromTimestamp($object['cancellation_effective_date']);
                } else {
                    $subscription->ends_at = null;
                }
            }

            // Status...
            if (isset($object['status'])) {
                $subscription->paddle_status = $object['status'];
            }

            $subscription->save();
        }
    }
}
