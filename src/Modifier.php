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
}
