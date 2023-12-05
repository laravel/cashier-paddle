<?php

namespace Workbench\App\Models;

use Illuminate\Foundation\Auth\User as Authenticable;
use Laravel\Paddle\Billable;

class User extends Authenticable
{
    use Billable;

    protected $guarded = [];
}
