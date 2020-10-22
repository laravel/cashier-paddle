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
}
