<?php

namespace JeffersonGoncalves\QuebecConsent\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use JeffersonGoncalves\QuebecConsent\Concerns\HasConsents;

class User extends Authenticatable
{
    use HasConsents;

    protected $guarded = [];

    public $timestamps = false;
}
