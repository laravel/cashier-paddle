<?php

namespace Laravel\Paddle;

use Spatie\Url\Url;

class SubscriptionBuilder
{
    /**
     * The model that is subscribing.
     *
     * @var \Laravel\Paddle\Billable
     */
    protected $billable;

    /**
     * The name of the subscription.
     *
     * @var string
     */
    protected $name;

    /**
     * The plan of the subscription.
     *
     * @var int
     */
    protected $plan;

    /**
     * The quantity of the subscription.
     *
     * @var int
     */
    protected $quantity = 1;

    /**
     * The days until the trial will expire.
     *
     * @var int|null
     */
    protected $trialDays;

    /**
     * Indicates that the trial should end immediately.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * The coupon code being applied to the customer.
     *
     * @var string|null
     */
    protected $coupon;

    /**
     * The metadata to apply to the subscription.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * The return url which will be triggered upon starting the subscription.
     *
     * @var string|null
     */
    protected $returnTo;

    /**
     * Create a new subscription builder instance.
     *
     * @param  \Laravel\Paddle\Billable  $owner
     * @param  string  $name
     * @param  int  $plan
     * @return void
     */
    public function __construct($owner, $name, $plan)
    {
        $this->name = $name;
        $this->plan = $plan;
        $this->billable = $owner;
    }

    /**
     * Specify the quantity of the subscription.
     *
     * @param  int  $quantity
     * @return $this
     */
    public function quantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Specify the number of days of the trial.
     *
     * @param  int  $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Force the trial to end immediately.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * The coupon to apply to a new subscription.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * The metadata to apply to a new subscription.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function withMetadata(array $metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * The return url which will be triggered upon starting the subscription.
     *
     * @param  string  $returnTo
     * @param  string  $checkoutParameter
     * @return $this
     */
    public function returnTo($returnTo, $checkoutParameter = 'checkout')
    {
        $this->returnTo = (string) Url::fromString($returnTo)
            ->withQueryParameter($checkoutParameter, '{checkout_hash}');

        return $this;
    }

    /**
     * Generate a pay link for a subscription.
     *
     * @param  array  $options
     * @return string
     */
    public function create(array $options = [])
    {
        $payload = array_merge($this->buildPayload(), $options);

        if (! is_null($trialDays = $this->getTrialEndForPayload())) {
            $payload['trial_days'] = $trialDays;

            // Paddle will immediately charge the plan price for the trial days
            // so we'll need to explicitly set the prices to 0 for the first charge.
            if ($trialDays !== 0) {
                $prices = $payload['prices'] ?? $this->getPlanPricesForPayload();
                $payload['prices'] = [];

                foreach ($prices as $key => $price) {
                    [$currency] = explode(':', $price);

                    $payload['prices'][] = $currency.':0';
                }
            }
        }

        $payload['passthrough'] = array_merge($this->metadata, [
            'subscription_name' => $this->name,
        ]);

        return $this->billable->chargeProduct($this->plan, $payload);
    }

    /**
     * Build the payload for subscription creation.
     *
     * @return array
     */
    protected function buildPayload()
    {
        return [
            'coupon_code' => (string) $this->coupon,
            'quantity' => $this->quantity,
            'return_url' => $this->returnTo,
        ];
    }

    /**
     * Get the days until the trial will expire for the Paddle payload.
     *
     * @return int|null
     */
    protected function getTrialEndForPayload()
    {
        if ($this->skipTrial) {
            return 0;
        }

        return $this->trialDays;
    }

    /**
     * Get the plan prices for the Paddle payload.
     *
     * @return array
     */
    protected function getPlanPricesForPayload()
    {
        $plan = Cashier::post(
            '/subscription/plans', $this->billable->paddleOptions(['plan' => $this->plan])
        )['response'][0];

        return collect($plan['initial_price'])
            ->map(function ($price, $currency) {
                return "$currency:$price";
            })
            ->values()
            ->all();
    }
}
