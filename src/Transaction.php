<?php

namespace Laravel\Paddle;

use Exception;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Money\Currency;

/**
 * @property \Laravel\Paddle\Billable $billable
 * @property \Laravel\Paddle\Subscription|null $subscription
 */
class Transaction extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'billed_at' => 'datetime',
    ];

    /**
     * Get the billable model related to the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Get the subscription related to the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Cashier::$subscriptionModel, 'paddle_subscription_id', 'paddle_id');
    }

    /**
     * Get the total amount that was paid.
     *
     * @return string
     */
    public function total()
    {
        return Cashier::formatAmount($this->total, $this->currency());
    }

    /**
     * Get the total tax that was paid.
     *
     * @return string
     */
    public function tax()
    {
        return Cashier::formatAmount($this->tax, $this->currency());
    }

    /**
     * Get the used currency for the transaction.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->currency);
    }

    /**
     * Get the URL to download the invoice.
     *
     * @return string|null
     */
    public function invoicePdf()
    {
        return Cashier::api('GET', "transactions/{$this->paddle_id}/invoice")['data']['url'] ?? null;
    }

    /**
     * Get the URL to download the invoice.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToInvoicePdf()
    {
        if ($url = $this->invoicePdf()) {
            return redirect($url);
        }

        throw new LogicException('The transaction does not have an invoice PDF.');
    }

    /**
     * Refund the transaction for a given price and optional amount.
     *
     * @param  string  $reason
     * @param  string|array  $price
     * @return array
     */
    public function refund($reason, $prices = [])
    {
        return $this->adjust('refund', $reason, $prices);
    }

    /**
     * Credit the transaction for a given price and optional amount.
     *
     * @param  string  $reason
     * @param  string|array  $price
     * @return array
     */
    public function credit($reason, $prices = [])
    {
        return $this->adjust('credit', $reason, $prices);
    }

    /**
     * Adjust the transaction for a given price and optional amount.
     *
     * @param  string  $type
     * @param  string  $reason
     * @param  string|array  $price
     * @return array
     */
    public function adjust($type, $reason, $prices = [])
    {
        if ($this->status !== 'billed' && $this->status !== 'completed') {
            throw new LogicException('Only "billed" or "completed" transactions can be adjusted.');
        }

        $lineItems = $this->asPaddleTransaction()['details']['line_items'];
        $prices = (array) $prices;

        $items = collect($lineItems)
            ->filter(function (array $lineItem) use ($prices) {
                // If no specific prices were given, we'll refund the entire transaction.
                if (empty($prices)) {
                    return true;
                }

                return in_array($lineItem['price_id'], $prices);
            })
            ->map(function (array $lineItem) use ($prices) {
                // If a specific amount was given to refund for this price, we'll use that.
                // Otherwise, we'll refund the entire line item.
                $amount = isset($prices[$lineItem['price_id']])
                    ? $prices[$lineItem['price_id']]
                    : null;

                return array_filter([
                    'item_id' => $lineItem['id'],
                    'type' => $amount ? 'partial' : 'full',
                    'amount' => $amount,
                ]);
            })
            ->values()
            ->all();

        if (empty($items)) {
            $prices = implode(', ', $prices);

            throw new Exception(
                "Cannot find line items with price ID's `{$prices}` for transaction `{$this->paddle_id}`."
            );
        }

        return Cashier::api('POST', 'adjustments', [
            'action' => $type,
            'transaction_id' => $this->paddle_id,
            'reason' => $reason,
            'items' => $items,
        ])['data'];
    }

    /**
     * Get the transaction as a Paddle transaction response.
     *
     * @return array
     */
    public function asPaddleTransaction()
    {
        return Cashier::api('GET', "transactions/{$this->paddle_id}")['data'];
    }
}
