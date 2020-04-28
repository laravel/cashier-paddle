<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Model;
use Laravel\Paddle\Billable;

class User extends Model
{
    use Billable;
}
