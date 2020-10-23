<?php

namespace Laravel\Paddle;

use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    const UPDATED_AT = null;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the subscription related to the modifier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Deletes itself in the database and on Paddle.
     *
     * @return bool|null
     */
    public function delete()
    {
        $payload = $this->subscription->billable->paddleOptions([
            'modifier_id' => $this->paddle_id,
        ]);

        Cashier::post('/subscription/modifiers/delete', $payload);

        return parent::delete();
    }
}
