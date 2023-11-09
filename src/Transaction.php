<?php

namespace Laravel\Paddle;

use Illuminate\Database\Eloquent\Model;
use Laravel\Paddle\Concerns\ManagesAmounts;
use LogicException;
use Money\Currency;

class Transaction extends Model
{
    use ManagesAmounts;

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
     * Get the billable model related to the receipt.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Get the subscription related to the receipt.
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
        return $this->formatAmount($this->total);
    }

    /**
     * Get the total tax that was paid.
     *
     * @return string
     */
    public function tax()
    {
        return $this->formatAmount($this->tax);
    }

    /**
     * Get the used currency for the receipt.
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
}
