<?php

namespace Laravel\Paddle\Concerns;

use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;

trait PerformsCharges
{
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param  int  $amount
     * @param  string  $title
     * @param  array  $options
     * @return string
     */
    public function charge($amount, $title, array $options = [])
    {
        $options = array_merge([
            'title' => $title,
            'webhook_url' => Cashier::webhookUrl(),
            'prices' => [
                'EUR:'.$amount,
            ],
        ], $options, $this->paddleOptions());

        $response = Http::post("https://vendors.paddle.com/api/2.0/product/generate_pay_link", $options)
            ->body();

        // dd(json_decode($response, true));

        return json_decode($response, true)['response']['url'];
    }
}
